<?php

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
        Schema::create('job_offer_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_offer_id')->constrained('job_offers')->cascadeOnDelete();
            $table->foreignId('requester_id')->constrained('users');
            $table->foreignId('approver_id')->nullable()->constrained('users');
            $table->string('status')->default('pending'); // pending, approved, rejected, completed
            $table->string('reason'); // Motivo del cambio
            $table->text('justification'); // Explicación detallada
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_offer_change_requests');
    }
};
