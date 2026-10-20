<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signature_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->morphs('signable');
            $table->nullableMorphs('owner');
            $table->string('signer_name');
            $table->string('signer_email');
            $table->string('signer_phone')->nullable();
            $table->string('signer_role')->nullable();
            $table->string('token_hash', 64)->unique();
            $table->string('status', 20)->default('pending');
            $table->string('document_path');
            $table->string('document_hash', 64);
            $table->string('document_version')->nullable();
            $table->string('otp_channel', 20)->default('mail');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('signed_pdf_path')->nullable();
            $table->text('decline_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('props')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['signable_type', 'signable_id', 'status']);
            $table->index(['owner_type', 'owner_id', 'status']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signature_requests');
    }
};
