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
        Schema::table('users', function (Blueprint $table) {
            // An invited user is inactive with no usable password until they accept.
            $table->timestamp('invited_at')->nullable()->after('active');
            $table->string('invitation_token')->nullable()->after('invited_at')->index();
            $table->timestamp('invitation_accepted_at')->nullable()->after('invitation_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['invited_at', 'invitation_token', 'invitation_accepted_at']);
        });
    }
};
