<?php

namespace App\Jobs;

use App\Enums\NotificationChannel;
use App\Mail\PriceAlertMail;
use App\Models\Alert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendPriceAlert implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $alertId,
        public string $productName,
        public string $store,
        public string $url,
        public string $targetPrice,
        public string $currentPrice,
        public string $currency,
    ) {}

    public function handle(): void
    {
        $alert = Alert::query()
            ->with('productMonitor.user')
            ->findOrFail($this->alertId);

        if (! in_array($alert->notification_channel, [
            NotificationChannel::ALL,
            NotificationChannel::EMAIL,
        ], true)) {
            return;
        }

        $user = $alert->productMonitor->user;

        Mail::to($user->email)->send(new PriceAlertMail(
            userName: $user->name,
            productName: $this->productName,
            store: $this->store,
            url: $this->url,
            targetPrice: $this->targetPrice,
            currentPrice: $this->currentPrice,
            currency: $this->currency,
        ));
    }
}
