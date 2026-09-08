<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetBusinessData extends Command
{
    protected $signature = 'dune:reset-business-data {--force : Skip the confirmation prompt}';

    protected $description = 'Wipes all business/transactional data (orders, stock, products, tables, reservations, etc.). User accounts, roles and permissions are kept untouched.';

    private const TABLES = [
        'audit_logs',
        'event_payments',
        'events',
        'employees',
        'expenses',
        'purchase_lines',
        'purchases',
        'suppliers',
        'stock_movements',
        'recipe_items',
        'recipes',
        'ingredients',
        'reservation_tables',
        'reservations',
        'cash_movements',
        'payments',
        'order_items',
        'orders',
        'cash_sessions',
        'product_price_histories',
        'products',
        'restaurant_tables',
        'customers',
        'categories',
        'zones',
    ];

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm(
            'This will permanently delete ALL business data (orders, stock, products, tables, reservations, etc.) '
            .'on this database. User accounts, roles and permissions are kept. Continue?'
        )) {
            $this->info('Aborted, nothing was deleted.');

            return self::SUCCESS;
        }

        Schema::disableForeignKeyConstraints();

        foreach (self::TABLES as $table) {
            DB::table($table)->truncate();
            $this->line("Truncated {$table}");
        }

        Schema::enableForeignKeyConstraints();

        $this->info('Business data reset complete. Users, roles and permissions were kept.');

        return self::SUCCESS;
    }
}
