<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * The full V1 permission catalog, grouped by module, per the Dune ERP
     * cahier des charges (section 2 "Périmètre fonctionnel" and section 15
     * "Rôles & permissions"). Only the modules built so far (users, roles,
     * audit) are wired to routes/policies; the rest are pre-declared so
     * later phases assign them to roles without a fresh migration each time.
     */
    public const CATALOG = [
        'audit' => ['view'],
        'users' => ['view', 'manage'],
        'roles' => ['manage'],

        'orders' => ['view', 'create', 'update', 'cancel', 'delete', 'discount'],
        'payments' => ['view', 'create', 'refund'],
        'cash' => ['view', 'open', 'close', 'movement'],

        'tables' => ['manage'],
        'reservations' => ['view', 'create', 'update', 'cancel'],

        'kitchen' => ['view'],
        'bar' => ['view'],

        'products' => ['view', 'create', 'update', 'delete'],
        'categories' => ['manage'],
        'recipes' => ['manage'],

        'stock' => ['view', 'adjust', 'inventory'],
        'purchases' => ['manage'],
        'suppliers' => ['manage'],

        'customers' => ['manage'],
        'events' => ['manage'],
        'expenses' => ['manage'],
        'employees' => ['manage'],

        'reports' => ['view'],
    ];

    public function run(): void
    {
        foreach (self::CATALOG as $module => $actions) {
            foreach ($actions as $action) {
                Permission::findOrCreate("{$module}.{$action}", 'web');
            }
        }
    }
}
