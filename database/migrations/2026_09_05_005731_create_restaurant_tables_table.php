<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('zones')->restrictOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('capacity');
            $table->enum('status', ['available', 'occupied', 'reserved', 'cleaning', 'inactive'])
                ->default('available');
            $table->timestamps();

            $table->unique(['zone_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_tables');
    }
};
