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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            // CPF is PII: stored encrypted. `cpf_last4` (plaintext) drives display +
            // search; `cpf_hash` (deterministic HMAC) is the blind index for uniqueness
            // and existence checks, since the encrypted value can't be matched.
            $table->text('cpf')->nullable();
            $table->string('cpf_last4', 4)->nullable();
            $table->string('cpf_hash', 64)->nullable()->unique();
            $table->date('birth_date')->nullable();
            $table->string('address')->nullable();
            $table->string('status')->default('active');
            $table->string('blood_type')->nullable();
            $table->json('allergies')->nullable();
            // Denormalized rollups — written by Scheduling/Financial, read-only here.
            $table->decimal('ltv', 12, 2)->default(0);
            $table->date('last_visit_date')->nullable();
            $table->date('first_visit_date')->nullable();
            $table->unsignedInteger('total_appointments')->default(0);
            $table->unsignedInteger('missed_appointments')->default(0);
            $table->string('lead_source')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
