@extends('layouts.app')

@section('title', 'Plan de salle')

@section('content')
    @php
        $statusColors = [
            'available' => 'success',
            'occupied' => 'danger',
            'reserved' => 'warning',
            'cleaning' => 'secondary',
            'inactive' => 'dark',
        ];
    @endphp

    @foreach ($zones as $zone)
        <h2 class="h6 text-uppercase text-muted mt-4">{{ $zone->name }}</h2>
        <div class="row row-cols-2 row-cols-md-4 row-cols-lg-6 g-3">
            @foreach ($zone->tables as $table)
                @php $order = $activeOrdersByTable->get($table->id); @endphp
                <div class="col">
                    <div class="card border-{{ $statusColors[$table->status] }} h-100">
                        <div class="card-body text-center p-2">
                            <div class="fw-bold">{{ $table->name }}</div>
                            <div class="text-muted small">{{ $table->capacity }} pers.</div>
                            <span class="badge text-bg-{{ $statusColors[$table->status] }} badge-status my-1">{{ $table->status }}</span>

                            @if ($order)
                                <a href="{{ route('orders.show', $order) }}" class="btn btn-outline-primary btn-sm d-block mt-1">
                                    Voir commande
                                </a>
                            @elseif (in_array($table->status, ['available', 'reserved']))
                                @can('orders.create')
                                    <a href="{{ route('orders.create') }}?table_id={{ $table->id }}" class="btn btn-outline-primary btn-sm d-block mt-1">
                                        Ouvrir
                                    </a>
                                @endcan
                            @elseif ($table->status === 'cleaning')
                                @can('orders.update')
                                    <form method="POST" action="{{ route('floor-plan.mark-available', $table) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-secondary btn-sm d-block w-100 mt-1">
                                            Disponible
                                        </button>
                                    </form>
                                @endcan
                            @endif

                            @if ($order)
                                @can('orders.update')
                                    <form method="POST" action="{{ route('floor-plan.transfer', $table) }}" class="d-flex gap-1 mt-1">
                                        @csrf
                                        <select name="new_table_id" class="form-select form-select-sm">
                                            <option value="">Transférer vers…</option>
                                            @foreach ($zones as $z)
                                                @foreach ($z->tables as $t)
                                                    @if ($t->id !== $table->id && in_array($t->status, ['available', 'reserved']))
                                                        <option value="{{ $t->id }}">{{ $z->name }} — {{ $t->name }}</option>
                                                    @endif
                                                @endforeach
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-outline-secondary btn-sm">OK</button>
                                    </form>
                                @endcan
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach
@endsection
