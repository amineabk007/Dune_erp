<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->dateTime('event_date');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->unsignedInteger('guest_count')->nullable();
            $table->text('description')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->timestamps();

            $table->index(['event_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
