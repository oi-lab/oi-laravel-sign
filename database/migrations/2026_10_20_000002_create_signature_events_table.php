<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signature_events', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('signature_request_id')->constrained('signature_requests')->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('props')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['signature_request_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signature_events');
    }
};
