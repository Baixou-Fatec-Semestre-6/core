<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PriceAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public string $productName,
        public string $store,
        public string $url,
        public string $targetPrice,
        public string $currentPrice,
        public string $currency,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'BAIXOU - Alerta de preço atingido',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.price-alert',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
