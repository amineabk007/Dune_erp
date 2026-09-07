<?php

namespace App\Livewire;

use App\Models\OrderItem;
use App\Services\OrderService;
use DomainException;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ProductionBoard extends Component
{
    /**
     * A fixed, distinct palette so the same table always gets the same
     * color on both the kitchen and bar screens (hashed by table id),
     * making it obvious at a glance which items belong together.
     */
    private const TABLE_COLORS = [
        '#e63946', '#457b9d', '#2a9d8f', '#f4a261', '#8338ec',
        '#e07a5f', '#06d6a0', '#ef476f', '#5f0f40', '#3a86ff',
    ];

    public string $destination;

    public ?string $error = null;

    /**
     * The status_changed_at of the most recent "sent" item already seen,
     * kept as a plain "Y-m-d H:i:s" string (not a Carbon instance, and not
     * wall-clock time) — a string round-trips through Livewire's snapshot
     * exactly and shares the database column's own precision, so a poll
     * can never spuriously miss or duplicate an alert.
     */
    public ?string $lastCheckedAt = null;

    public function mount(string $destination): void
    {
        $this->destination = $destination;

        $latest = OrderItem::where('destination', $destination)
            ->where('status', 'sent')
            ->max('status_changed_at');

        // The 1-second back-date guards against the DB column's whole-second
        // precision: without it, an item sent in the very same wall-clock
        // second the screen was opened would tie with "now" and be missed
        // by the strict ">" comparison in checkForNewOrders().
        $this->lastCheckedAt = $latest ?: now()->subSecond()->format('Y-m-d H:i:s');
    }

    #[Computed]
    public function items(): Collection
    {
        return OrderItem::where('destination', $this->destination)
            ->whereIn('status', ['sent', 'preparing', 'ready'])
            ->with('order.table')
            ->orderBy('status_changed_at')
            ->get()
            ->groupBy('order_id');
    }

    public function tableColor(?int $tableId): string
    {
        if (! $tableId) {
            return '#6c757d';
        }

        return self::TABLE_COLORS[$tableId % count(self::TABLE_COLORS)];
    }

    /**
     * wire:poll target. The body is intentionally empty: every request
     * (this one included) ends in render(), which is where the actual
     * new-order check happens — this method just gives the poll (and
     * tests) an explicit action to call.
     */
    public function poll(): void {}

    public function advance(int $itemId, string $status): void
    {
        if (! auth()->user()->can($this->destination.'.view')) {
            $this->error = "Vous n'avez pas accès à cet écran.";

            return;
        }

        $item = OrderItem::where('destination', $this->destination)->findOrFail($itemId);

        try {
            app(OrderService::class)->advanceItemStatus($item, $status);
        } catch (DomainException $e) {
            $this->error = $e->getMessage();
        }

        unset($this->items);
    }

    /**
     * Called on every poll (and on mount's initial render). Any item sent
     * to this destination since the last checkpoint triggers a browser-side
     * sound alert; mount() seeds the checkpoint from existing data so
     * pre-existing items never fire a false alert when the screen is first
     * opened. The checkpoint only ever advances to a real item's own
     * timestamp (never to "now"), so a quiet poll can never cause a later
     * item to be silently skipped.
     */
    private function checkForNewOrders(): void
    {
        $newlySent = OrderItem::where('destination', $this->destination)
            ->where('status', 'sent')
            ->where('status_changed_at', '>', $this->lastCheckedAt)
            ->with('order.table')
            ->get();

        if ($newlySent->isNotEmpty()) {
            $tables = $newlySent->pluck('order.table.name')->filter()->unique()->implode(', ');

            $this->dispatch(
                'dune-notify',
                message: 'Nouvelle commande envoyée'.($tables !== '' ? ' — '.$tables : '').' !'
            );

            $this->lastCheckedAt = $newlySent->max('status_changed_at')->format('Y-m-d H:i:s');
        }
    }

    public function render()
    {
        $this->checkForNewOrders();

        return view('livewire.production-board');
    }
}
