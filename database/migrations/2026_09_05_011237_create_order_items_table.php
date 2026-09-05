<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            // Snapshots: the order must keep exactly what was sold even if the
            // product's name/price/category changes or is deactivated later.
            $table->string('product_name');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('tax_rate', 5, 2);
            $table->enum('destination', ['kitchen', 'bar', 'none'])->default('none');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->string('notes')->nullable();
            $table->string('kitchen_note')->nullable();
            $table->enum('status', ['new', 'sent', 'preparing', 'ready', 'served', 'cancelled'])->default('new');
            $table->timestamp('status_changed_at')->nullable();
            $table->foreignId('status_changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index('destination');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
