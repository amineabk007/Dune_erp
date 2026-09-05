<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ReportController extends Controller implements HasMiddleware
{
    public function __construct(private readonly ReportService $reports) {}

    public static function middleware(): array
    {
        return [new Middleware('permission:reports.view')];
    }

    public function index(Request $request): View
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now();

        return view('reports.index', [
            'from' => $from,
            'to' => $to,
            'sales' => $this->reports->salesSummary($from, $to),
            'topProducts' => $this->reports->topProducts($from, $to),
            'expenses' => $this->reports->expensesSummary($from, $to),
            'pnl' => $this->reports->profitAndLoss($from, $to),
        ]);
    }
}
