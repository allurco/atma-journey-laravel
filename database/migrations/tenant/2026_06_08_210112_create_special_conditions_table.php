<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Clinic-maintained catalog of standing care needs surfaced on the "Agendar"
     * form. Seeded with the common Brazilian categories; the admin edits/archives
     * them in Configurações ▸ Condições especiais.
     */
    public function up(): void
    {
        Schema::create('special_conditions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        $now = now();
        $defaults = ['PCD', 'Idoso', 'Criança', 'Autista', 'Gestante', 'Outros cuidados especiais'];

        DB::table('special_conditions')->insert(array_map(
            fn (string $name): array => ['name' => $name, 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            $defaults,
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('special_conditions');
    }
};
