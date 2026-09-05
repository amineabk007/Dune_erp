<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Dune ERP') }} @hasSection('title')&mdash; @yield('title')@endif</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
</head>
<body>
    <div class="d-flex" style="min-height: 100vh;">
        <nav class="dune-sidebar p-3" style="width: 250px; flex-shrink: 0;">
            <a href="{{ route('dashboard') }}" class="dune-brand text-decoration-none d-block mb-4 fs-4">
                DUNE ERP
            </a>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        Dashboard
                    </a>
                </li>
                @can('viewAny', App\Models\User::class)
                <li class="nav-item">
                    <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                        Utilisateurs
                    </a>
                </li>
                @endcan
                @can('viewAny', Spatie\Permission\Models\Role::class)
                <li class="nav-item">
                    <a href="{{ route('roles.index') }}" class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                        Rôles &amp; permissions
                    </a>
                </li>
                @endcan
                @can('viewAny', App\Models\AuditLog::class)
                <li class="nav-item">
                    <a href="{{ route('audit.index') }}" class="nav-link {{ request()->routeIs('audit.*') ? 'active' : '' }}">
                        Audit
                    </a>
                </li>
                @endcan

                @canany(['orders.view', 'cash.view'])
                <li class="nav-item mt-3 mb-1 px-2 text-uppercase small text-secondary">Opérations</li>
                @endcanany
                @can('orders.view')
                <li class="nav-item">
                    <a href="{{ route('orders.index') }}" class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}">
                        Commandes / POS
                    </a>
                </li>
                @endcan
                @can('cash.view')
                <li class="nav-item">
                    <a href="{{ route('cash-sessions.index') }}" class="nav-link {{ request()->routeIs('cash-sessions.*') ? 'active' : '' }}">
                        Caisse
                    </a>
                </li>
                @endcan

                @canany(['tables.manage', 'categories.manage', 'products.view', 'customers.manage'])
                <li class="nav-item mt-3 mb-1 px-2 text-uppercase small text-secondary">Référentiels</li>
                @endcanany
                @can('tables.manage')
                <li class="nav-item">
                    <a href="{{ route('zones.index') }}" class="nav-link {{ request()->routeIs('zones.*') ? 'active' : '' }}">
                        Zones
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('tables.index') }}" class="nav-link {{ request()->routeIs('tables.*') ? 'active' : '' }}">
                        Tables
                    </a>
                </li>
                @endcan
                @can('categories.manage')
                <li class="nav-item">
                    <a href="{{ route('categories.index') }}" class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                        Catégories
                    </a>
                </li>
                @endcan
                @can('products.view')
                <li class="nav-item">
                    <a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                        Produits
                    </a>
                </li>
                @endcan
                @can('customers.manage')
                <li class="nav-item">
                    <a href="{{ route('customers.index') }}" class="nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                        Clients
                    </a>
                </li>
                @endcan
            </ul>
        </nav>

        <div class="flex-grow-1">
            <header class="border-bottom bg-white">
                <div class="d-flex justify-content-between align-items-center px-4 py-2">
                    <h1 class="h5 mb-0">@yield('title', 'Dashboard')</h1>
                    <div class="d-flex align-items-center gap-3">
                        <span class="text-muted small">
                            {{ auth()->user()->name }}
                            @foreach (auth()->user()->getRoleNames() as $roleName)
                                <span class="badge text-bg-secondary badge-status">{{ $roleName }}</span>
                            @endforeach
                        </span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary btn-sm">Déconnexion</button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="p-4">
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
    @livewireScripts
</body>
</html>
