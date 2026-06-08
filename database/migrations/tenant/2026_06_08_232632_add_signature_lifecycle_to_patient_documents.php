<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Signature lifecycle for documents sent to a patient. A null signature_status
     * marks a plain uploaded file (exam/laudo); Pendente/Assinado drive the
     * Documentos tab's pending vs signed sections. The token (hashed) authenticates
     * the public signing link; the signed_* columns are the click-to-sign trail.
     */
    public function up(): void
    {
        Schema::table('patient_documents', function (Blueprint $table) {
            $table->foreignId('document_template_id')->nullable()->after('patient_id')->constrained()->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->after('document_template_id')->constrained()->nullOnDelete();
            $table->string('signature_status')->nullable()->after('category');
            $table->timestamp('sent_at')->nullable()->after('uploaded_at');
            $table->timestamp('signed_at')->nullable()->after('sent_at');
            $table->string('signature_token')->nullable()->unique()->after('signed_at');
            $table->string('signed_ip')->nullable()->after('signature_token');
            $table->string('signed_user_agent')->nullable()->after('signed_ip');
        });
    }

    public function down(): void
    {
        Schema::table('patient_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('document_template_id');
            $table->dropConstrainedForeignId('appointment_id');
            $table->dropColumn(['signature_status', 'sent_at', 'signed_at', 'signature_token', 'signed_ip', 'signed_user_agent']);
        });
    }
};
