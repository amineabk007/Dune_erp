<div>
    <div class="d-flex justify-content-end mb-2">
        <span class="badge text-bg-secondary badge-status fs-6">{{ $order->status }}</span>
    </div>

    @if ($status)
        <div class="alert alert-success py-2">{{ $status }}</div>
    @endif
    @if ($error)
        <div class="alert alert-danger py-2">{{ $error }}</div>
    @endif

    @if (in_array($order->status, ['paid', 'cancelled']))
        <div class="alert alert-info">
            Cette commande est {{ $order->status === 'paid' ? 'payée' : 'annulée' }} et ne peut plus être modifiée.
            @if ($order->status === 'cancelled' && $order->cancel_reason)
                Motif : {{ $order->cancel_reason }}.
            @endif
        </div>

        <div class="card mb-3">
            <div class="card-header">Articles</div>
            <ul class="list-group list-group-flush">
                @forelse ($this->items as $item)
                    <li class="list-group-item d-flex justify-content-between">
                        <span>{{ $item->quantity }} × {{ $item->product_name }}
                            <span class="badge text-bg-light border badge-status">{{ $item->status }}</span>
                        </span>
                        <span>{{ number_format($item->line_total, 2) }} DH</span>
                    </li>
                @empty
                    <li class="list-group-item text-muted">Aucun article.</li>
                @endforelse
                <li class="list-group-item d-flex justify-content-between fw-bold">
                    <span>Total</span><span>{{ number_format($order->total, 2) }} DH</span>
                </li>
            </ul>
        </div>
    @else
        <div class="row">
            <div class="col-md-7">
                @can('orders.update')
                    <div class="card mb-3">
                        <div class="card-header">Catalogue</div>
                        <div class="card-body">
                            <div class="d-flex gap-2 mb-2">
                                <input type="text" class="form-control form-control-sm" placeholder="Rechercher un produit..."
                                       wire:model.live.debounce.300ms="search">
                                <select class="form-select form-select-sm" wire:model.live="categoryFilter" style="max-width: 220px;">
                                    <option value="">Toutes catégories</option>
                                    @foreach ($this->categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="row row-cols-2 row-cols-lg-3 g-2" style="max-height: 420px; overflow-y: auto;">
                                @forelse ($this->products as $product)
                                    <div class="col">
                                        <button type="button" wire:click="addProduct({{ $product->id }})"
                                                class="btn btn-outline-secondary w-100 h-100 text-start p-2">
                                            <div class="fw-semibold small">{{ $product->name }}</div>
                                            <div class="text-muted small">{{ number_format($product->price, 2) }} DH</div>
                                        </button>
                                    </div>
                                @empty
                                    <p class="text-muted">Aucun produit trouvé.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endcan

                <div class="card mb-3">
                    <div class="card-header">Actions</div>
                    <div class="card-body d-flex flex-wrap gap-2">
                        @can('orders.update')
                            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="sendToProduction">
                                Envoyer en cuisine/bar
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="markServed">
                                Marquer servi
                            </button>
                        @endcan
                        @can('orders.cancel')
                            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="collapse" data-bs-target="#cancelForm">
                                Annuler la commande
                            </button>
                        @endcan
                    </div>
                    @can('orders.cancel')
                        <div class="collapse" id="cancelForm">
                            <div class="card-body border-top">
                                <div class="input-group">
                                    <input type="text" wire:model="cancelReason" class="form-control" placeholder="Motif d'annulation (obligatoire)">
                                    <button type="button" class="btn btn-danger" wire:click="cancelOrder"
                                            onclick="return confirm('Confirmer l\'annulation ?')">
                                        Confirmer
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endcan
                </div>
            </div>

            <div class="col-md-5">
                <div class="card mb-3">
                    <div class="card-header">Articles</div>
                    <ul class="list-group list-group-flush">
                        @forelse ($this->items as $item)
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-semibold">{{ $item->product_name }}</div>
                                        <div class="text-muted small">
                                            {{ number_format($item->unit_price, 2) }} DH ·
                                            <span class="badge text-bg-light border badge-status">{{ $item->status }}</span>
                                            <span class="badge text-bg-light border badge-status">{{ $item->destination }}</span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-semibold">{{ number_format($item->line_total, 2) }} DH</div>
                                        @can('orders.update')
                                            @if ($item->status === 'new')
                                                <div class="btn-group btn-group-sm mt-1">
                                                    <button type="button" class="btn btn-outline-secondary" wire:click="decrementQuantity({{ $item->id }})">−</button>
                                                    <span class="btn btn-outline-secondary disabled">{{ $item->quantity }}</span>
                                                    <button type="button" class="btn btn-outline-secondary" wire:click="incrementQuantity({{ $item->id }})">+</button>
                                                </div>
                                            @else
                                                <div class="small text-muted mt-1">qté {{ $item->quantity }}</div>
                                            @endif
                                        @endcan
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">Aucun article pour le moment.</li>
                        @endforelse
                    </ul>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between"><span>Sous-total</span><span>{{ number_format($order->subtotal, 2) }} DH</span></div>
                        <div class="d-flex justify-content-between"><span>Remise</span><span>-{{ number_format($order->discount_amount, 2) }} DH</span></div>
                        <div class="d-flex justify-content-between"><span>Taxe</span><span>{{ number_format($order->tax_amount, 2) }} DH</span></div>
                        <div class="d-flex justify-content-between fw-bold fs-5"><span>Total</span><span>{{ number_format($order->total, 2) }} DH</span></div>
                        <div class="d-flex justify-content-between text-muted"><span>Payé</span><span>{{ number_format($order->amount_paid, 2) }} DH</span></div>
                    </div>
                </div>

                @can('orders.discount')
                    <div class="card mb-3">
                        <div class="card-header">Remise</div>
                        <div class="card-body">
                            <div class="row g-2">
                                <div class="col-auto">
                                    <input type="number" step="0.01" min="0" wire:model="discountAmount" class="form-control form-control-sm" placeholder="Montant DH">
                                </div>
                                <div class="col-auto">
                                    <input type="text" wire:model="discountReason" class="form-control form-control-sm" placeholder="Motif">
                                </div>
                                <div class="col-auto">
                                    <button type="button" class="btn btn-outline-primary btn-sm" wire:click="applyDiscount">Appliquer</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endcan

                @can('payments.create')
                    <div class="card mb-3">
                        <div class="card-header">
                            Encaisser — reste dû : <strong>{{ $order->balanceDue() }} DH</strong>
                        </div>
                        <div class="card-body">
                            @if (! $this->currentSession)
                                <p class="text-danger mb-0">Aucune session de caisse ouverte.</p>
                            @elseif ((float) $order->balanceDue() > 0)
                                <div class="row g-2">
                                    <div class="col-auto">
                                        <select wire:model="paymentMethod" class="form-select form-select-sm">
                                            <option value="cash">Espèces</option>
                                            <option value="card">Carte</option>
                                            <option value="transfer">Virement</option>
                                            <option value="other">Autre</option>
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <input type="number" step="0.01" min="0.01" wire:model="paymentAmount" class="form-control form-control-sm" placeholder="Montant">
                                    </div>
                                    <div class="col-auto">
                                        <input type="text" wire:model="paymentReference" class="form-control form-control-sm" placeholder="Référence (optionnel)">
                                    </div>
                                    <div class="col-auto">
                                        <button type="button" class="btn btn-primary btn-sm" wire:click="recordPayment">Encaisser</button>
                                    </div>
                                </div>
                            @else
                                <p class="text-success mb-0">Commande intégralement payée.</p>
                            @endif
                        </div>
                    </div>
                @endcan
            </div>
        </div>
    @endif

    @if ($this->payments->isNotEmpty())
        <div class="card mb-3">
            <div class="card-header">Paiements</div>
            <ul class="list-group list-group-flush">
                @foreach ($this->payments as $payment)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            {{ $payment->method }} — {{ number_format($payment->amount, 2) }} DH
                            par {{ $payment->receivedBy->name }}
                            @if ($payment->refunded)
                                <span class="badge text-bg-danger badge-status">remboursé</span>
                            @endif
                        </span>
                        @can('payments.refund')
                            @if (! $payment->refunded)
                                <div class="d-flex gap-2">
                                    <input type="text" wire:model="refundReasons.{{ $payment->id }}"
                                           class="form-control form-control-sm" placeholder="Motif">
                                    <button type="button" class="btn btn-outline-danger btn-sm"
                                            wire:click="refundPayment({{ $payment->id }})"
                                            onclick="return confirm('Confirmer le remboursement ?')">
                                        Rembourser
                                    </button>
                                </div>
                            @endif
                        @endcan
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
