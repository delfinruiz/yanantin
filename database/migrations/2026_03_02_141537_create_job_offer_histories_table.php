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
        Schema::create('job_offer_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_offer_id')->constrained('job_offers')->cascadeOnDelete();
            $table->foreignId('changed_by_id')->constrained('users');
            $table->foreignId('change_request_id')->nullable()->constrained('job_offer_change_requests');
            $table->json('snapshot_data'); // Datos previos al cambio
            $table->string('change_reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_offer_histories');
    }
};
