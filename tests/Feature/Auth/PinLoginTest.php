<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Concerns\SeedsRolesAndPermissions;
use Tests\TestCase;

class PinLoginTest extends TestCase
{
    use RefreshDatabase, SeedsRolesAndPermissions;

    public function test_pin_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/pin-login');

        $response->assertOk();
        $response->assertSee('DUNE ERP');
    }

    public function test_a_user_can_authenticate_with_their_correct_pin(): void
    {
        $user = User::factory()->create(['pin' => Hash::make('12345678')]);

        $response = $this->post('/pin-login', ['pin' => '12345678']);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/dashboard');
    }

    public function test_authentication_fails_with_a_wrong_pin(): void
    {
        User::factory()->create(['pin' => Hash::make('12345678')]);

        $response = $this->post('/pin-login', ['pin' => '99999999']);

        $this->assertGuest();
        $response->assertSessionHasErrors('pin');
    }

    public function test_a_user_without_a_configured_pin_cannot_be_matched(): void
    {
        User::factory()->create(['pin' => null]);

        $response = $this->post('/pin-login', ['pin' => '12345678']);

        $this->assertGuest();
        $response->assertSessionHasErrors('pin');
    }

    public function test_a_deactivated_user_cannot_authenticate_with_their_pin(): void
    {
        User::factory()->inactive()->create(['pin' => Hash::make('12345678')]);

        $response = $this->post('/pin-login', ['pin' => '12345678']);

        $this->assertGuest();
        $response->assertSessionHasErrors('pin');
    }

    public function test_pin_must_be_exactly_eight_digits(): void
    {
        User::factory()->create(['pin' => Hash::make('12345678')]);

        $this->post('/pin-login', ['pin' => '1234'])->assertSessionHasErrors('pin');
        $this->assertGuest();
    }

    public function test_pin_login_is_rate_limited_after_too_many_attempts(): void
    {
        User::factory()->create(['pin' => Hash::make('12345678')]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/pin-login', ['pin' => '00000000']);
        }

        $response = $this->post('/pin-login', ['pin' => '00000000']);

        $response->assertSessionHasErrors('pin');
        $this->assertStringContainsString(
            'Trop de tentatives de connexion',
            collect(session('errors')->get('pin'))->first()
        );

        RateLimiter::clear('pin-login|127.0.0.1');
    }
}
