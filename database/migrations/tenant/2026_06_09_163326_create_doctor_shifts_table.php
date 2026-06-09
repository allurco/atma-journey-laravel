<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('doctor_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            // Wall-clock 'HH:mm' strings (tenant-local), matching `appointments`.
            $table->string('start_time', 5);
            $table->string('end_time', 5);
            $table->string('unit')->nullable();
            $table->timestamps();

            // The lane query + the booking gate both look up a doctor's day.
            $table->index(['doctor_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_shifts');
    }
};
