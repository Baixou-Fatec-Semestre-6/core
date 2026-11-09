<?php

namespace App\Console\Commands;

use App\Enums\ScrapeRequestStatus;
use App\Models\ProductListing;
use App\Models\ScrapeRequest;
use App\Services\PriceService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToWriteFile;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;
use Throwable;

class ConsumeScrapeResults extends Command
{
    private const MAX_IMAGE_BYTES = 5_242_880;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:consume-scrape-results';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scrape results from the Python scraper';

    /**
     * Execute the console command.
     */
    public function handle(PriceService $pricesService): int
    {
        $connection = new AMQPStreamConnection(
            config('rabbitmq.host'),
            config('rabbitmq.port'),
            config('rabbitmq.user'),
            config('rabbitmq.password'),
        );
        $channel = $connection->channel();
        $channel->basic_qos(0, 10, false); // at most 10 unacked messages at a time

        $channel->basic_consume(
            config('rabbitmq.results_queue'),
            '',
            false, // no_local
            false, // no_ack = false, so we ack manually
            false, // exclusive
            false, // nowait
            function (AMQPMessage $message) use ($pricesService): void {
                try {
                    $this->process($message, $pricesService);
                    $message->ack();
                } catch (Throwable $e) {
                    $this->markRequestFailed($message, $e);
                    Log::error('Scrape result failed', [
                        'routing_key' => $message->delivery_info['routing_key'] ?? null,
                        'body' => $message->getBody(),
                        'error' => $e->getMessage(),
                    ]);
                    report($e);
                    $message->nack(false, false); // drop it; don't loop forever on a bad message
                }
            }
        );

        $this->info('Waiting for scrape results...');

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        return self::SUCCESS;
    }

    /**
     * Process a single scrape result message.
     */
    private function process(AMQPMessage $message, PriceService $pricesService): void
    {
        $data = json_decode($message->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $routingKey = $message->delivery_info['routing_key'];

        $context = [
            'request_id' => $data['request_id'] ?? null,
            'listing_id' => $data['listing_id'] ?? null,
            'routing_key' => $routingKey,
        ];

        $resultStatus = match ($routingKey) {
            'price.scraped' => ScrapeRequestStatus::SUCCEEDED,
            'price.failed' => ScrapeRequestStatus::FAILED,
            default => throw new \UnexpectedValueException("Unknown routing key: {$routingKey}"),
        };
        $scrapeRequest = isset($data['request_id'])
            ? ScrapeRequest::query()->where('request_id', $data['request_id'])->first()
            : null;

        if ($scrapeRequest !== null && in_array($scrapeRequest->status, [
            ScrapeRequestStatus::SUCCEEDED,
            ScrapeRequestStatus::FAILED,
            ScrapeRequestStatus::IGNORED,
        ], true)) {
            Log::info('Duplicate scrape result ignored', $context);

            return;
        }

        $listing = ProductListing::find($data['listing_id']);

        if ($listing === null) {
            $this->finishRequest($scrapeRequest, ScrapeRequestStatus::IGNORED, 'Listing no longer exists.');
            Log::warning('Scrape result ignored: listing not found', $context);

            return;
        }

        $scrapedAt = Carbon::parse($data['scraped_at'])
            ->setTimezone(config('app.timezone'));

        if ($listing->last_scraped_at && $scrapedAt->lte($listing->last_scraped_at)) {
            $this->finishRequest($scrapeRequest, ScrapeRequestStatus::IGNORED, 'Older than current listing data.');
            Log::warning('Scrape result ignored: older than current data', $context + [
                'scraped_at' => $scrapedAt->toIso8601String(),
                'last_scraped_at' => $listing->last_scraped_at->toIso8601String(),
            ]);

            return;
        }

        $errorReason = $resultStatus === ScrapeRequestStatus::FAILED
            ? (string) ($data['reason'] ?? 'Unknown error')
            : null;

        $product = $listing->product;
        $existingImageSourceUrl = $product?->image_source_url;
        $existingImagePath = $product?->image_path;
        $existingImageIsScraped = filled($existingImagePath)
            || (filled($existingImageSourceUrl)
                && $this->validatedImageUrl($existingImageSourceUrl) !== null);
        $storedImagePath = null;
        $imageSourceUrl = null;

        if (
            $resultStatus === ScrapeRequestStatus::SUCCEEDED
            && filled($data['image_url'] ?? null)
            && (! filled($existingImageSourceUrl) || $existingImageIsScraped)
        ) {
            $imageSourceUrl = $this->validatedImageUrl($data['image_url']);

            if ($imageSourceUrl === null) {
                Log::warning('Scraped product image URL ignored because it is invalid', $context);
            } else {
                $storedImagePath = $this->downloadImage($imageSourceUrl, $context);
            }
        }

        $imageWasSaved = false;

        try {
            DB::transaction(function () use ($data, $errorReason, $existingImagePath, $existingImageSourceUrl, $imageSourceUrl, $listing, $pricesService, $resultStatus, $scrapeRequest, $scrapedAt, $storedImagePath, &$imageWasSaved): void {
                if ($resultStatus === ScrapeRequestStatus::SUCCEEDED) {
                    $pricesService->recordPrice($listing, (float) $data['price'], $scrapedAt);

                    if ($storedImagePath !== null) {
                        $productQuery = $listing->product();

                        if (filled($existingImageSourceUrl)) {
                            $productQuery->where('image_source_url', $existingImageSourceUrl);
                        } else {
                            $productQuery->where(function ($query): void {
                                $query->whereNull('image_source_url')
                                    ->orWhere('image_source_url', '');
                            });
                        }

                        if (filled($existingImagePath)) {
                            $productQuery->where('image_path', $existingImagePath);
                        } else {
                            $productQuery->whereNull('image_path');
                        }

                        $imageWasSaved = $productQuery->update([
                            'image_source_url' => $imageSourceUrl,
                            'image_path' => $storedImagePath,
                        ]) > 0;
                    }
                } else {
                    $pricesService->recordFailure($listing, $errorReason ?? 'Unknown error', $scrapedAt);
                }

                $this->finishRequest($scrapeRequest, $resultStatus, $errorReason);
            });
        } catch (Throwable $exception) {
            if ($storedImagePath !== null) {
                $this->deleteStoredImage($storedImagePath, $context);
            }

            throw $exception;
        }

        if ($storedImagePath !== null && ! $imageWasSaved) {
            $this->deleteStoredImage($storedImagePath, $context);
        }

        Log::info('Scrape result processed', $context);
    }

    private function validatedImageUrl(mixed $imageUrl): ?string
    {
        if (! is_string($imageUrl)) {
            return null;
        }

        $imageUrl = trim($imageUrl);
        $validated = Validator::make(
            ['image_url' => $imageUrl],
            ['image_url' => ['required', 'url:https', 'max:2048']],
        )->passes();

        if (! $validated) {
            return null;
        }

        $url = parse_url($imageUrl);
        $host = strtolower($url['host'] ?? '');

        if (
            ($url['scheme'] ?? null) !== 'https'
            || $host !== 'm.media-amazon.com'
            || isset($url['user'])
            || isset($url['pass'])
            || (isset($url['port']) && $url['port'] !== 443)
        ) {
            return null;
        }

        return $imageUrl;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function downloadImage(string $imageUrl, array $context): ?string
    {
        try {
            $response = Http::connectTimeout(3)
                ->timeout(12)
                ->withoutRedirecting()
                ->withOptions(['stream' => true])
                ->get($imageUrl);
        } catch (ConnectionException $exception) {
            Log::warning('Scraped product image could not be downloaded', $context + [
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('Scraped product image request returned an unsuccessful status', $context + [
                'status' => $response->status(),
            ]);

            return null;
        }

        $contentLength = $response->header('Content-Length');

        if (is_numeric($contentLength) && (int) $contentLength > self::MAX_IMAGE_BYTES) {
            Log::warning('Scraped product image exceeded the maximum file size', $context);

            return null;
        }

        $contentType = strtolower(trim(explode(';', (string) $response->header('Content-Type'), 2)[0]));
        $mimeTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        if (! array_key_exists($contentType, $mimeTypes)) {
            Log::warning('Scraped product image has an unsupported content type', $context + [
                'content_type' => $contentType,
            ]);

            return null;
        }

        $stream = $response->toPsrResponse()->getBody();
        $contents = '';

        try {
            while (! $stream->eof()) {
                $chunk = $stream->read(min(8192, self::MAX_IMAGE_BYTES + 1 - strlen($contents)));

                if ($chunk === '') {
                    break;
                }

                $contents .= $chunk;

                if (strlen($contents) > self::MAX_IMAGE_BYTES) {
                    Log::warning('Scraped product image exceeded the maximum file size', $context);

                    return null;
                }
            }
        } catch (RuntimeException $exception) {
            Log::warning('Scraped product image download was incomplete', $context + [
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        $imageInfo = @getimagesizefromstring($contents);

        if (! is_array($imageInfo) || ($imageInfo['mime'] ?? null) !== $contentType) {
            Log::warning('Scraped product image content is invalid or does not match its content type', $context);

            return null;
        }

        $path = 'products/images/'.Str::uuid().'.'.$mimeTypes[$contentType];

        try {
            $stored = Storage::disk('public')->put($path, $contents);
        } catch (UnableToWriteFile $exception) {
            Log::warning('Scraped product image could not be stored', $context + [
                'error' => $exception->getMessage(),
            ]);

            return null;
        }

        if (! $stored) {
            Log::warning('Scraped product image could not be stored', $context);

            return null;
        }

        return $path;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function deleteStoredImage(string $path, array $context): void
    {
        try {
            $deleted = Storage::disk('public')->delete($path);
        } catch (UnableToDeleteFile $exception) {
            Log::warning('Unreferenced scraped product image could not be deleted', $context + [
                'path' => $path,
                'error' => $exception->getMessage(),
            ]);

            return;
        }

        if (! $deleted) {
            Log::warning('Unreferenced scraped product image could not be deleted', $context + [
                'path' => $path,
            ]);
        }
    }

    private function finishRequest(
        ?ScrapeRequest $scrapeRequest,
        ScrapeRequestStatus $status,
        ?string $errorReason = null,
    ): void {
        if ($scrapeRequest === null) {
            return;
        }

        $scrapeRequest->newQuery()
            ->whereKey($scrapeRequest->id)
            ->whereNotIn('status', [
                ScrapeRequestStatus::SUCCEEDED,
                ScrapeRequestStatus::FAILED,
                ScrapeRequestStatus::IGNORED,
            ])
            ->update([
                'status' => $status,
                'completed_at' => now(),
                'error_reason' => $errorReason,
                'updated_at' => now(),
            ]);
    }

    private function markRequestFailed(AMQPMessage $message, Throwable $exception): void
    {
        try {
            $data = json_decode($message->getBody(), true, flags: JSON_THROW_ON_ERROR);

            if (! isset($data['request_id'])) {
                return;
            }

            $scrapeRequest = ScrapeRequest::query()
                ->where('request_id', $data['request_id'])
                ->first();

            $this->finishRequest(
                $scrapeRequest,
                ScrapeRequestStatus::FAILED,
                $exception->getMessage(),
            );
        } catch (Throwable $trackingException) {
            Log::error('Scrape request failure could not be recorded', [
                'error' => $trackingException->getMessage(),
            ]);
            report($trackingException);
        }
    }
}
