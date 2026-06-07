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
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            // The prescribing doctor (required at creation; kept nullable so the
            // historical record survives if the doctor is later removed).
            $table->foreignId('doctor_id')->nullable()->constrained()->nullOnDelete();
            // Array-shape: [{drug, dose, frequency, duration}].
            $table->json('items');
            $table->text('notes')->nullable();
            $table->timestamp('issued_at');
            $table->timestamps();

            $table->index(['patient_id', 'issued_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
