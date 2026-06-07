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
        Schema::table('clinics', function (Blueprint $table) {
            // When true, the prescription PDF suppresses the generated letterhead
            // (the clinic prints onto its own pre-printed receituário) and reserves
            // top space for the pre-printed header.
            $table->boolean('uses_custom_prescription_paper')->default(false);
            $table->unsignedInteger('prescription_header_margin_mm')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clinics', function (Blueprint $table) {
            $table->dropColumn(['uses_custom_prescription_paper', 'prescription_header_margin_mm']);
        });
    }
};
