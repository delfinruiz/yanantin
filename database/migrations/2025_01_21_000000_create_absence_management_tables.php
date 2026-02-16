<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absence_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color')->nullable(); // For UI badges
            $table->boolean('is_vacation')->default(false); // Deducts from vacation balance
            $table->boolean('requires_approval')->default(true);
            $table->integer('max_days_allowed')->nullable();
            $table->boolean('allows_half_day')->default(false);
            $table->timestamps();
        });

        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('date');
            $table->boolean('is_recurring')->default(false); // If true, repeats every year
            $table->timestamps();
        });

        Schema::create('vacation_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_profile_id')->constrained()->onDelete('cascade');
            $table->decimal('days', 8, 2); // Can be positive (accrual) or negative (usage)
            $table->string('type'); // 'accrual', 'usage', 'adjustment'
            $table->string('description')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable(); // Can link to absence_request_id
            $table->timestamps();
        });

        Schema::create('absence_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_profile_id')->constrained()->onDelete('cascade');
            $table->foreignId('absence_type_id')->constrained();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days_requested', 8, 2);
            $table->text('reason')->nullable();
            $table->string('status')->default('pending'); // pending, approved_supervisor, approved_hr, rejected
            $table->text('supervisor_comment')->nullable();
            $table->foreignId('supervisor_id')->nullable()->constrained('users');
            $table->timestamp('supervisor_approved_at')->nullable();
            $table->text('hr_comment')->nullable();
            $table->foreignId('hr_user_id')->nullable()->constrained('users');
            $table->timestamp('hr_approved_at')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absence_requests');
        Schema::dropIfExists('vacation_ledgers');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('absence_types');
    }
};
