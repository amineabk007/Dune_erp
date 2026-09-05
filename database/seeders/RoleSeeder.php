<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Default permission grants per role, following the principle of least
     * privilege from the cahier des charges (section 15). "admin" is handled
     * separately: it bypasses every check via Gate::before and is also given
     * every permission here so the role-permission matrix screen reflects it.
     */
    public const GRANTS = [
        'direction' => [
            'audit.view', 'reports.view', 'users.view',
            'orders.view', 'payments.view', 'payments.refund', 'cash.view',
            'stock.view', 'products.view', 'reservations.view',
            'customers.manage', 'events.manage', 'expenses.manage',
            'employees.manage', 'purchases.manage', 'suppliers.manage',
            'recipes.manage', 'categories.manage', 'tables.manage',
        ],
        'manager' => [
            'orders.view', 'orders.create', 'orders.update', 'orders.cancel',
            'payments.view', 'payments.create',
            'cash.view', 'cash.open', 'cash.close',
            'stock.view', 'stock.adjust', 'stock.inventory',
            'products.view', 'products.create', 'products.update', 'categories.manage',
            'reservations.view', 'reservations.create', 'reservations.update', 'reservations.cancel',
            'tables.manage', 'kitchen.view', 'bar.view',
            'customers.manage', 'events.manage', 'expenses.manage', 'employees.manage',
            'purchases.manage', 'suppliers.manage', 'recipes.manage',
            'reports.view', 'users.view',
        ],
        'caissier' => [
            'orders.view', 'orders.create', 'orders.update',
            'payments.view', 'payments.create',
            'cash.view', 'cash.open', 'cash.close',
            'reservations.view', 'products.view', 'customers.manage',
        ],
        'serveur' => [
            'tables.manage', 'orders.view', 'orders.create', 'orders.update',
            'reservations.view', 'reservations.create', 'reservations.update',
            'products.view',
        ],
        'cuisine' => [
            'kitchen.view', 'orders.view', 'stock.view',
        ],
        'bar' => [
            'bar.view', 'orders.view', 'stock.view',
        ],
        'stock' => [
            'stock.view', 'stock.adjust', 'stock.inventory',
            'purchases.manage', 'suppliers.manage', 'products.view', 'recipes.manage', 'reports.view',
        ],
        'comptable' => [
            'expenses.manage', 'reports.view', 'payments.view',
            'purchases.manage', 'suppliers.manage',
        ],
    ];

    public function run(): void
    {
        $admin = Role::findOrCreate('admin', 'web');
        $admin->syncPermissions(Permission::all());

        foreach (self::GRANTS as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($permissions);
        }
    }
}
