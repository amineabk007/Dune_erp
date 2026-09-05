<?php

namespace App\Mail;

use App\Models\Ingredient;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LowStockAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Ingredient $ingredient) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Stock bas : {$this->ingredient->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.low-stock-alert',
            with: ['ingredient' => $this->ingredient],
        );
    }
}
