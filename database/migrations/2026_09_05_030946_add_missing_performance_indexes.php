<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 9 QA pass: index the columns actually filtered by ReportService,
     * CashSessionService and StockService — payments and cash_sessions are
     * queried on nearly every request that touches the caisse or dashboard.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['refunded', 'created_at']);
        });

        Schema::table('cash_sessions', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('restaurant_tables', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('cash_movements', function (Blueprint $table) {
            $table->index('type');
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['refunded', 'created_at']);
        });

        Schema::table('cash_sessions', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('restaurant_tables', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('cash_movements', function (Blueprint $table) {
            $table->dropIndex(['type']);
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
        });
    }
};
