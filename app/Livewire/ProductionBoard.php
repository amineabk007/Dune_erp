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
    public string $destination;

    public ?string $error = null;

    public function mount(string $destination): void
    {
        $this->destination = $destination;
    }

    #[Computed]
    public function items(): Collection
    {
        return OrderItem::where('destination', $this->destination)
            ->whereIn('status', ['sent', 'preparing', 'ready'])
            ->with('order.table')
            ->orderBy('status_changed_at')
            ->get();
    }

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

    public function render()
    {
        return view('livewire.production-board');
    }
}
