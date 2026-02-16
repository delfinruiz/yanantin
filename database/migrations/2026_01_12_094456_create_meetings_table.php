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
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->string('zoom_id')->nullable()->index();
            $table->string('topic');
            $table->text('description')->nullable();
            $table->dateTime('start_time');
            $table->integer('duration')->comment('in minutes');
            $table->string('timezone')->default('UTC');
            $table->string('status')->default('scheduled'); // scheduled, active, finished, canceled
            $table->foreignId('host_id')->constrained('users')->cascadeOnDelete();
            $table->string('password')->nullable();
            $table->text('start_url')->nullable();
            $table->text('join_url')->nullable();
            $table->integer('type')->default(2); // 2 = scheduled
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
