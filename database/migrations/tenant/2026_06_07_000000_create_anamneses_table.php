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
        Schema::create('anamneses', function (Blueprint $table) {
            $table->id();
            // One anamnese per patient — the current medical history, edited in place
            // (upsert), not an append-only log. Unique enforces the one-per-patient rule.
            $table->foreignId('patient_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('chief_complaint')->nullable();
            $table->text('history')->nullable();
            $table->text('medications')->nullable();
            $table->text('family_history')->nullable();
            $table->json('lifestyle')->nullable();
            // The user who last edited — nullable, no cascade (the record outlives staff).
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anamneses');
    }
};
