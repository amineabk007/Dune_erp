<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventPaymentRequest;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Customer;
use App\Models\Event;
use App\Services\EventService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class EventController extends Controller implements HasMiddleware
{
    public function __construct(private readonly EventService $events) {}

    public static function middleware(): array
    {
        return [new Middleware('permission:events.manage')];
    }

    public function index(): View
    {
        $events = Event::with('customer')->latest('event_date')->paginate(20);

        return view('events.index', compact('events'));
    }

    public function create(): View
    {
        $customers = Customer::orderBy('name')->get();

        return view('events.create', compact('customers'));
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $event = $this->events->create($request->user(), $request->validated());

        return redirect()->route('events.show', $event)->with('status', 'Événement créé.');
    }

    public function show(Event $event): View
    {
        $event->load(['customer', 'createdBy', 'payments.receivedBy']);

        return view('events.show', compact('event'));
    }

    public function edit(Event $event): View
    {
        $customers = Customer::orderBy('name')->get();

        return view('events.edit', compact('event', 'customers'));
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $this->events->update($event, $request->validated());

        return redirect()->route('events.show', $event)->with('status', 'Événement mis à jour.');
    }

    public function storePayment(StoreEventPaymentRequest $request, Event $event): RedirectResponse
    {
        try {
            $this->events->recordPayment(
                $event,
                $request->user(),
                $request->string('type')->toString(),
                $request->string('method')->toString(),
                (float) $request->input('amount'),
                $request->input('reference'),
            );
        } catch (DomainException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        return redirect()->route('events.show', $event)->with('status', 'Paiement enregistré.');
    }

    public function transition(Request $request, Event $event, string $status): RedirectResponse
    {
        $request->validate([
            'reason' => [$status === 'cancelled' ? 'required' : 'nullable', 'string', 'max:500'],
        ]);

        try {
            $this->events->transition($event, $status, $request->input('reason'));
        } catch (DomainException $e) {
            return back()->withErrors(['event' => $e->getMessage()]);
        }

        return redirect()->route('events.show', $event)->with('status', 'Statut mis à jour.');
    }
}
