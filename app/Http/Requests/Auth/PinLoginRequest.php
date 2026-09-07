<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class PinLoginRequest extends FormRequest
{
    /**
     * Anyone may attempt to log in; authorization happens in authenticate().
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pin' => ['required', 'digits:8'],
        ];
    }

    /**
     * Find the user matching this PIN and log them in, honoring the same
     * throttle spirit as the e-mail/password login. Keyed by IP only,
     * since — unlike the e-mail login — the PIN itself is both identity
     * and secret, so there's no separate username to scope the key on.
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $user = User::findByPin($this->string('pin'));

        if (! $user) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'pin' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        Auth::login($user);
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'pin' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return 'pin-login|'.$this->ip();
    }
}
