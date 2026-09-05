<div wire:poll.5s>
    @if ($error)
        <div class="alert alert-danger py-2">{{ $error }}</div>
    @endif

    <div class="row row-cols-1 row-cols-md-3 g-3">
        @forelse ($this->items as $item)
            <div class="col">
                <div class="card h-100 border-{{ $item->status === 'ready' ? 'success' : ($item->status === 'preparing' ? 'warning' : 'secondary') }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <span class="fw-semibold">{{ $item->order->order_number }}</span>
                            <span class="badge text-bg-light border badge-status">{{ $item->status }}</span>
                        </div>
                        <p class="text-muted small mb-1">
                            {{ $item->order->table->name ?? 'Vente directe' }} ·
                            envoyé {{ $item->status_changed_at?->diffForHumans() ?? $item->created_at->diffForHumans() }}
                        </p>
                        <p class="mb-1"><strong>{{ $item->quantity }} × {{ $item->product_name }}</strong></p>
                        @if ($item->kitchen_note)
                            <p class="text-danger small mb-2">Note : {{ $item->kitchen_note }}</p>
                        @endif

                        <div class="d-flex gap-2">
                            @if ($item->status === 'sent')
                                <button type="button" class="btn btn-warning btn-sm" wire:click="advance({{ $item->id }}, 'preparing')">
                                    Démarrer préparation
                                </button>
                            @elseif ($item->status === 'preparing')
                                <button type="button" class="btn btn-success btn-sm" wire:click="advance({{ $item->id }}, 'ready')">
                                    Marquer prêt
                                </button>
                            @elseif ($item->status === 'ready')
                                <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="advance({{ $item->id }}, 'served')">
                                    Marquer servi
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-muted">Aucun article en attente.</p>
        @endforelse
    </div>
</div>
