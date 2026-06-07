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
        Schema::create('exam_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            // Optionally linked to the uploaded exam document the result was read from.
            $table->foreignId('patient_document_id')->nullable()->constrained()->nullOnDelete();
            $table->string('exam_type');
            $table->date('collected_at')->nullable();
            // `manual` here (PRD-6); `ai` when PRD-10 extracts findings automatically.
            $table->string('source')->default('manual');
            $table->timestamps();

            $table->index('patient_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_results');
    }
};
