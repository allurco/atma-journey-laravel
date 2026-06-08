<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Operational/administrative context captured on the "Agendar" form: the unit
     * (unidade) the visit happens at and a free-form scheduling note (observação).
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('unit')->nullable()->after('service_type');
            $table->text('notes')->nullable()->after('unit');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['unit', 'notes']);
        });
    }
};
