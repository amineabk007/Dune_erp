<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();
            $table->foreignId('cash_session_id')->constrained('cash_sessions')->restrictOnDelete();
            $table->enum('method', ['cash', 'card', 'transfer', 'other']);
            $table->decimal('amount', 12, 2);
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->boolean('refunded')->default(false);
            $table->timestamp('refunded_at')->nullable();
            $table->foreignId('refunded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('refund_reason')->nullable();
            $table->string('reference')->nullable();
            $table->timestamps();

            $table->index(['cash_session_id', 'method']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
