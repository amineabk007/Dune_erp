@extends('layouts.app')

@section('title', 'Audit')

@section('content')
    <form method="GET" class="row g-2 mb-3">
        <div class="col-auto">
            <select name="module" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Tous les modules</option>
                @foreach ($modules as $module)
                    <option value="{{ $module }}" {{ request('module') === $module ? 'selected' : '' }}>{{ $module }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <select name="action" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">Toutes les actions</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>{{ $action }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <table class="table table-striped bg-white align-middle">
        <thead>
            <tr>
                <th>Date</th>
                <th>Utilisateur</th>
                <th>Action</th>
                <th>Module</th>
                <th>Enregistrement</th>
                <th>Motif</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($logs as $log)
                <tr>
                    <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                    <td>{{ $log->user?->name ?? 'système' }}</td>
                    <td><span class="badge text-bg-secondary badge-status">{{ $log->action }}</span></td>
                    <td>{{ $log->module }}</td>
                    <td>{{ $log->record_id ?? '—' }}</td>
                    <td>{{ $log->reason ?? '—' }}</td>
                    <td>{{ $log->ip_address ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $logs->links() }}
@endsection
