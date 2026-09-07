<?php

namespace App\Livewire;

use App\Models\OrderItem;
use Livewire\Component;

/**
 * Silent, invisible component mounted once in the main layout for anyone
 * who can create orders (serveur/caissier/manager). Polls for items that
 * just became "ready" on one of their own open orders and fires the shared
 * dune-notify browser event (sound + dismiss banner), so a server working
 * elsewhere in the app knows a plate is waiting without watching a screen.
 */
class ReadyOrdersNotifier extends Component
{
    /**
     * The status_changed_at of the most recent "ready" item already seen,
     * kept as a plain "Y-m-d H:i:s" string (not a Carbon instance, and not
     * wall-clock time) — see ProductionBoard::$lastCheckedAt for why.
     */
    public ?string $lastCheckedAt = null;

    public function mount(): void
    {
        $latest = auth()->check()
            ? OrderItem::where('status', 'ready')
                ->whereHas('order', fn ($query) => $query->where('user_id', auth()->id()))
                ->max('status_changed_at')
            : null;

        $this->lastCheckedAt = $latest ?: now()->subSecond()->format('Y-m-d H:i:s');
    }

    /**
     * wire:poll target; see ProductionBoard::poll() for why it's empty.
     */
    public function poll(): void {}

    private function checkReady(): void
    {
        if (! auth()->check() || ! auth()->user()->can('orders.create')) {
            return;
        }

        $newlyReady = OrderItem::where('status', 'ready')
            ->where('status_changed_at', '>', $this->lastCheckedAt)
            ->whereHas('order', function ($query) {
                $query->where('user_id', auth()->id())->whereNotIn('status', ['paid', 'cancelled']);
            })
            ->with('order.table')
            ->get();

        if ($newlyReady->isNotEmpty()) {
            $tables = $newlyReady->pluck('order.table.name')->filter()->unique()->implode(', ');

            $this->dispatch(
                'dune-notify',
                message: 'Commande prête à servir'.($tables !== '' ? ' — '.$tables : '').' !'
            );

            $this->lastCheckedAt = $newlyReady->max('status_changed_at')->format('Y-m-d H:i:s');
        }
    }

    public function render()
    {
        $this->checkReady();

        return view('livewire.ready-orders-notifier');
    }
}
