<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('file_item_shares', function (Blueprint $table) {
            $table->id();

            $table->foreignId('file_item_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('permission', ['view', 'edit'])->default('view');

            $table->timestamps();

            $table->unique(['file_item_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_item_shares');
    }
};
