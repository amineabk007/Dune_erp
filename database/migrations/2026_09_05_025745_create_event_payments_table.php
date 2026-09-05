<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->enum('type', ['deposit', 'balance', 'other'])->default('deposit');
            $table->enum('method', ['cash', 'card', 'transfer', 'other']);
            $table->decimal('amount', 12, 2);
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->string('reference')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_payments');
    }
};
