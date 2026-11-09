<?php

namespace App\Console\Commands;

use App\Enums\ScrapeRequestStatus;
use App\Models\ProductListing;
use App\Models\ScrapeRequest;
use App\Services\PriceService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

class ConsumeScrapeResults extends Command
{
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

        DB::transaction(function () use ($data, $errorReason, $listing, $pricesService, $resultStatus, $scrapeRequest, $scrapedAt): void {
            if ($resultStatus === ScrapeRequestStatus::SUCCEEDED) {
                $pricesService->recordPrice($listing, (float) $data['price'], $scrapedAt);
            } else {
                $pricesService->recordFailure($listing, $errorReason ?? 'Unknown error', $scrapedAt);
            }

            $this->finishRequest($scrapeRequest, $resultStatus, $errorReason);
        });

        Log::info('Scrape result processed', $context);
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
