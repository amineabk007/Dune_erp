<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class BarController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('permission:bar.view')];
    }

    public function __invoke(): View
    {
        return view('production.board', ['destination' => 'bar', 'title' => 'Bar']);
    }
}
