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
        Schema::create('pipeline_cards', function (Blueprint $table) {
            $table->id();
            // One active card per patient — enforced at the DB, not just the action.
            $table->foreignId('patient_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('stage')->index();
            $table->string('treatment');
            $table->decimal('value', 12, 2)->default(0);
            $table->date('last_contact')->nullable();
            $table->string('contact_type')->default('whatsapp');
            // Financial (PRD-5) writes the link to an approved budget; nullable here.
            $table->foreignId('budget_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pipeline_cards');
    }
};
