<div wire:poll.5s="poll">
    @if ($error)
        <div class="alert alert-danger py-2">{{ $error }}</div>
    @endif

    @forelse ($this->items as $orderItems)
        @php
            $firstItem = $orderItems->first();
            $table = $firstItem->order->table;
            $color = $this->tableColor($table->id ?? null);
        @endphp
        <div class="mb-4" style="border-left: 6px solid {{ $color }}; border-radius: 4px;">
            <div class="d-flex align-items-center gap-2 px-2 py-1" style="background-color: {{ $color }}22;">
                <span class="fw-bold">{{ $table->name ?? 'Vente directe' }}</span>
                <span class="text-muted small">{{ $firstItem->order->order_number }}</span>
            </div>

            <div class="row row-cols-1 row-cols-md-3 g-3 p-2">
                @foreach ($orderItems as $item)
                    <div class="col">
                        <div class="card h-100 border-{{ $item->status === 'ready' ? 'success' : ($item->status === 'preparing' ? 'warning' : 'secondary') }}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <span class="badge text-bg-light border badge-status">{{ $item->statusLabel() }}</span>
                                </div>
                                <p class="text-muted small mb-1">
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
                @endforeach
            </div>
        </div>
    @empty
        <p class="text-muted">Aucun article en attente.</p>
    @endforelse
</div>
