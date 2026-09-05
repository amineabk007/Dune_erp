<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Reservation $reservation) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre réservation chez Dune Rooftop est confirmée',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reservation-confirmed',
            with: ['reservation' => $this->reservation],
        );
    }
}
