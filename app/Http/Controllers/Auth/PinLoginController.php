<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PinLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PinLoginController extends Controller
{
    public function create(): View
    {
        return view('auth.pin-login');
    }

    public function store(PinLoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $request->user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('dashboard'));
    }
}
