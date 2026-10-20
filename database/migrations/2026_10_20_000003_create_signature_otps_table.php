<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signature_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('signature_request_id')->constrained('signature_requests')->cascadeOnDelete();
            $table->string('code_hash', 64);
            $table->string('channel', 20);
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['signature_request_id', 'consumed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signature_otps');
    }
};
