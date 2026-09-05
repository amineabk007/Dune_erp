<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->foreignId('table_id')->constrained('restaurant_tables')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['reservation_id', 'table_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_tables');
    }
};
