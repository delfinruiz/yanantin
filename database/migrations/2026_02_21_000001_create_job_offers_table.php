<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_offers', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->text('requirements')->nullable();
            $table->text('benefits')->nullable();
            $table->string('location')->nullable();
            $table->string('contract_type')->nullable();
            $table->decimal('salary', 12, 2)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->date('deadline')->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_offers');
    }
};

