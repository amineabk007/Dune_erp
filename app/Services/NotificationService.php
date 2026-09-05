<?php

namespace App\Services;

use App\Mail\LowStockAlertMail;
use App\Mail\ReservationConfirmedMail;
use App\Models\Ingredient;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class NotificationService
{
    /**
     * A failed send (SMTP down, bad credentials) must never break the
     * business action it's attached to (confirming a reservation, recording
     * a stock movement) — it's logged and swallowed, not rethrown.
     */
    public function reservationConfirmed(Reservation $reservation): void
    {
        $email = $reservation->customer?->email;

        if (! $email) {
            return;
        }

        $this->send(fn () => Mail::to($email)->send(new ReservationConfirmedMail($reservation)));
    }

    public function lowStockAlert(Ingredient $ingredient): void
    {
        $recipients = User::permission('stock.adjust')
            ->where('is_active', true)
            ->whereNotNull('email')
            ->pluck('email');

        if ($recipients->isEmpty()) {
            return;
        }

        $this->send(fn () => Mail::to($recipients->all())->send(new LowStockAlertMail($ingredient)));
    }

    private function send(callable $dispatch): void
    {
        try {
            $dispatch();
        } catch (Throwable $e) {
            Log::warning('Notification email failed to send.', ['exception' => $e->getMessage()]);
        }
    }
}
