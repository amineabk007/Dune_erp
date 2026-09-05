<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use App\Services\ReservationService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ReservationController extends Controller implements HasMiddleware
{
    public function __construct(private readonly ReservationService $reservations) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:reservations.view', only: ['index', 'show']),
            new Middleware('permission:reservations.create', only: ['create', 'store']),
            new Middleware('permission:reservations.update', only: ['edit', 'update', 'confirm', 'seat', 'complete', 'createOrder']),
            new Middleware('permission:reservations.cancel', only: ['cancel', 'noShow']),
        ];
    }

    public function index(Request $request): View
    {
        $reservations = Reservation::with('customer', 'tables')
            ->when($request->filled('date'), fn ($q) => $q->whereDate('reserved_at', $request->date('date')))
            ->when(! $request->filled('date'), fn ($q) => $q->whereDate('reserved_at', now()->toDateString()))
            ->orderBy('reserved_at')
            ->paginate(30)
            ->withQueryString();

        return view('reservations.index', compact('reservations'));
    }

    public function create(): View
    {
        return view('reservations.create', [
            'customers' => Customer::orderBy('name')->get(),
            'tables' => RestaurantTable::with('zone')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreReservationRequest $request): RedirectResponse
    {
        try {
            $reservation = $this->reservations->create(
                $request->user(),
                (int) $request->input('customer_id'),
                Carbon::parse($request->input('reserved_at')),
                (int) $request->input('guests'),
                $request->input('table_ids'),
                $request->input('notes')
            );
        } catch (DomainException $e) {
            return back()->withErrors(['table_ids' => $e->getMessage()]);
        }

        return redirect()->route('reservations.show', $reservation)->with('status', 'Réservation créée.');
    }

    public function show(Reservation $reservation): View
    {
        return view('reservations.show', [
            'reservation' => $reservation->load('customer', 'tables.zone', 'createdBy', 'order'),
        ]);
    }

    public function edit(Reservation $reservation): View
    {
        return view('reservations.edit', [
            'reservation' => $reservation->load('tables'),
            'tables' => RestaurantTable::with('zone')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateReservationRequest $request, Reservation $reservation): RedirectResponse
    {
        try {
            $this->reservations->update(
                $reservation,
                Carbon::parse($request->input('reserved_at')),
                (int) $request->input('guests'),
                $request->input('table_ids'),
                $request->input('notes')
            );
        } catch (DomainException $e) {
            return back()->withErrors(['table_ids' => $e->getMessage()]);
        }

        return redirect()->route('reservations.show', $reservation)->with('status', 'Réservation mise à jour.');
    }

    public function confirm(Reservation $reservation): RedirectResponse
    {
        return $this->transition($reservation, 'confirmed');
    }

    public function seat(Reservation $reservation): RedirectResponse
    {
        return $this->transition($reservation, 'seated');
    }

    public function complete(Reservation $reservation): RedirectResponse
    {
        return $this->transition($reservation, 'completed');
    }

    public function cancel(Reservation $reservation): RedirectResponse
    {
        return $this->transition($reservation, 'cancelled');
    }

    public function noShow(Reservation $reservation): RedirectResponse
    {
        return $this->transition($reservation, 'no_show');
    }

    private function transition(Reservation $reservation, string $status): RedirectResponse
    {
        try {
            $this->reservations->transition($reservation, $status);
        } catch (DomainException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Statut de la réservation mis à jour.');
    }

    public function createOrder(Reservation $reservation): RedirectResponse
    {
        try {
            $order = $this->reservations->createOrderFromReservation($reservation, auth()->user());
        } catch (DomainException $e) {
            return back()->withErrors(['reservation' => $e->getMessage()]);
        }

        return redirect()->route('orders.show', $order);
    }
}
