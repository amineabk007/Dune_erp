<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class KitchenController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('permission:kitchen.view')];
    }

    public function __invoke(): View
    {
        return view('production.board', ['destination' => 'kitchen', 'title' => 'Cuisine']);
    }
}
