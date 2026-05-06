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
        Schema::create('employee_kpi_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('managed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('month');
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('tickets_resolved_target')->default(0);
            $table->unsignedInteger('first_response_minutes_target')->default(0);
            $table->decimal('csat_target', 5, 2)->default(0);
            $table->decimal('quality_score_target', 5, 2)->default(0);
            $table->json('manual_adjustments')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'user_id', 'month', 'year']);
            $table->index(['company_id', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_kpi_targets');
    }
};
