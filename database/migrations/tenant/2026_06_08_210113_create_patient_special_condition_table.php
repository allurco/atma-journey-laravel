<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tags patients with the care needs captured at booking. A patient may carry
     * several; the pair is unique.
     */
    public function up(): void
    {
        Schema::create('patient_special_condition', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('special_condition_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['patient_id', 'special_condition_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_special_condition');
    }
};
