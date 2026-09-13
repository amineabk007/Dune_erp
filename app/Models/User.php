<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'pin',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'pin',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'pin' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Only active users may authenticate; a deactivated account keeps its
     * historical records intact (orders, payments, audit logs) but cannot log in.
     */
    public function canAuthenticate(): bool
    {
        return $this->is_active;
    }

    /**
     * PINs are hashed like passwords, so they can't be looked up with a
     * direct WHERE — this checks each active user with a PIN configured.
     * Fine for a single restaurant's staff roster (tens of accounts, not
     * millions). Pass $except to exclude one user, e.g. when checking
     * whether a newly typed PIN collides with someone else's.
     */
    public static function findByPin(string $pin, ?int $except = null): ?self
    {
        return static::where('is_active', true)
            ->whereNotNull('pin')
            ->when($except, fn ($query) => $query->where('id', '!=', $except))
            ->get()
            ->first(fn (self $user) => Hash::check($pin, $user->pin));
    }

    /**
     * Where a device should land right after login. Management roles
     * (admin/manager/direction/comptable/stock) always get the full
     * dashboard, even if they also hold an operational role — they need
     * the overview, not a single tablet's screen. A single-purpose
     * tablet (kitchen, bar, cashier, floor) is expected to be logged
     * into with a staff account that holds only that one role.
     */
    public function defaultLandingRoute(): string
    {
        if ($this->hasAnyRole(['admin', 'manager', 'direction', 'comptable', 'stock'])) {
            return route('dashboard');
        }

        return match (true) {
            $this->hasRole('cuisine') => route('kitchen.index'),
            $this->hasRole('bar') => route('bar.index'),
            $this->hasRole('caissier') => route('orders.index'),
            $this->hasRole('serveur') => route('floor-plan.index'),
            default => route('dashboard'),
        };
    }
}
