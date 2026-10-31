<?php

namespace App\Console\Commands;

use App\Enums\ProductListingStatus;
use App\Models\ProductListing;
use App\Services\PriceService;
use Carbon\Carbon;
use Illuminate\Console\Command;
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
            'event_id' => $data['event_id'] ?? null,
            'listing_id' => $data['listing_id'] ?? null,
            'routing_key' => $routingKey,
        ];

        $listing = ProductListing::find($data['listing_id']);

        if ($listing === null) {
            Log::warning('Scrape result ignored: listing not found', $context);
            return;
        }

        $scrapedAt = Carbon::parse($data['scraped_at']);

        if ($listing->last_scraped_at && $scrapedAt->lte($listing->last_scraped_at)) {
            Log::warning('Scrape result ignored: older than current data', $context + [
                'scraped_at' => $scrapedAt->toIso8601String(),
                'last_scraped_at' => $listing->last_scraped_at->toIso8601String(),
            ]);
            return;
        }

        match ($routingKey) {
            'scrape.completed' => $pricesService->recordPrice($listing, (float) $data['price'], $scrapedAt),
            'scrape.failed' => $pricesService->recordFailure($listing, $data['reason'] ?? 'Unknown error', $scrapedAt),
            default => throw new \UnexpectedValueException("Unknown routing key: {$routingKey}"),
        };

        Log::info('Scrape result processed', $context);
    }

}
