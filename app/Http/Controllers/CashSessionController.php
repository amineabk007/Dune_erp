<?php

namespace App\Http\Controllers;

use App\Http\Requests\CashMovementRequest;
use App\Http\Requests\CloseCashSessionRequest;
use App\Http\Requests\OpenCashSessionRequest;
use App\Models\CashSession;
use App\Services\CashSessionService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class CashSessionController extends Controller implements HasMiddleware
{
    public function __construct(private readonly CashSessionService $service) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:cash.view', only: ['index', 'show']),
            new Middleware('permission:cash.open', only: ['create', 'store']),
            new Middleware('permission:cash.close', only: ['closeForm', 'close']),
            new Middleware('permission:cash.movement', only: ['storeMovement']),
        ];
    }

    public function index(): View
    {
        $sessions = CashSession::with('openedBy', 'closedBy')->latest('opened_at')->paginate(20);

        return view('cash-sessions.index', compact('sessions'));
    }

    public function create(): View
    {
        if ($current = $this->service->currentOpenSession()) {
            return view('cash-sessions.already-open', ['session' => $current]);
        }

        return view('cash-sessions.create');
    }

    public function store(OpenCashSessionRequest $request): RedirectResponse
    {
        try {
            $session = $this->service->open($request->user(), (float) $request->input('opening_cash'));
        } catch (DomainException $e) {
            return back()->withErrors(['opening_cash' => $e->getMessage()]);
        }

        return redirect()->route('cash-sessions.show', $session)->with('status', 'Session de caisse ouverte.');
    }

    public function show(CashSession $cashSession): View
    {
        return view('cash-sessions.show', [
            'session' => $cashSession->load('movements.user', 'payments.order'),
        ]);
    }

    public function storeMovement(CashMovementRequest $request, CashSession $cashSession): RedirectResponse
    {
        try {
            $this->service->recordMovement(
                $cashSession,
                $request->user(),
                $request->input('type'),
                (float) $request->input('amount'),
                $request->input('reason')
            );
        } catch (DomainException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return back()->with('status', 'Mouvement de caisse enregistré.');
    }

    public function closeForm(CashSession $cashSession): View
    {
        return view('cash-sessions.close', ['session' => $cashSession]);
    }

    public function close(CloseCashSessionRequest $request, CashSession $cashSession): RedirectResponse
    {
        try {
            $this->service->close(
                $cashSession,
                $request->user(),
                (float) $request->input('counted_cash'),
                $request->input('notes')
            );
        } catch (DomainException $e) {
            return back()->withErrors(['counted_cash' => $e->getMessage()]);
        }

        return redirect()->route('cash-sessions.show', $cashSession)->with('status', 'Session de caisse clôturée.');
    }
}
