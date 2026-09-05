<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('reserved_at');
            $table->unsignedSmallInteger('guests');
            $table->enum('status', ['pending', 'confirmed', 'seated', 'completed', 'cancelled', 'no_show'])
                ->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->timestamps();

            $table->index(['reserved_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
