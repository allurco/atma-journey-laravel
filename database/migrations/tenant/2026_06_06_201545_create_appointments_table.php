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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('procedure_id')->nullable()->constrained()->nullOnDelete();
            $table->string('service_type')->nullable();
            $table->date('date');
            // Wall-clock 'HH:mm' strings (tenant-local), matching the TS app.
            $table->string('start_time', 5);
            $table->string('end_time', 5);
            $table->string('status')->default('scheduled');
            $table->timestamps();

            $table->index(['date', 'start_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
