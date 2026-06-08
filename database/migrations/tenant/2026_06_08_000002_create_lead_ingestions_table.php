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
        Schema::create('lead_ingestions', function (Blueprint $table) {
            $table->id();
            $table->string('source')->nullable();
            $table->string('external_id')->nullable()->index();
            $table->string('phone')->nullable();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            // Whether this hit matched an existing patient (re-contact) vs created one.
            $table->boolean('matched')->default(false);
            $table->json('payload')->nullable();
            $table->timestamp('received_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_ingestions');
    }
};
