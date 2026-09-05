@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h5">Bienvenue, {{ $user->name }}</h2>
            <p class="text-muted mb-0">
                Rôle(s) : {{ $roles->implode(', ') ?: 'aucun' }}
            </p>
        </div>
    </div>

    @if ($kpis)
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">CA du jour (encaissé)</div>
                        <div class="fs-4 fw-bold">{{ number_format($kpis['today_revenue'], 2) }} DH</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Commandes en cours</div>
                        <div class="fs-4 fw-bold">{{ $kpis['open_orders'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Occupation des tables</div>
                        <div class="fs-4 fw-bold">{{ $kpis['occupied_tables'] }} / {{ $kpis['total_tables'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small">Alertes stock bas</div>
                        <div class="fs-4 fw-bold {{ $kpis['low_stock_count'] > 0 ? 'text-danger' : '' }}">
                            {{ $kpis['low_stock_count'] }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small">Session de caisse</div>
                    @if ($kpis['cash_session'])
                        <span class="badge text-bg-success badge-status">ouverte</span>
                        depuis {{ $kpis['cash_session']->opened_at->format('d/m/Y H:i') }}
                        par {{ $kpis['cash_session']->openedBy->name }}
                    @else
                        <span class="badge text-bg-secondary badge-status">aucune session ouverte</span>
                    @endif
                </div>
                @can('reports.view')
                    <a href="{{ route('reports.index') }}" class="btn btn-outline-primary btn-sm">Voir les rapports</a>
                @endcan
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">Permissions accordées</div>
        <div class="card-body">
            @if ($permissions->isEmpty())
                <p class="text-muted mb-0">Aucune permission accordée pour le moment.</p>
            @else
                <div class="d-flex flex-wrap gap-2">
                    @foreach ($permissions as $permission)
                        <span class="badge text-bg-light border">{{ $permission }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection
