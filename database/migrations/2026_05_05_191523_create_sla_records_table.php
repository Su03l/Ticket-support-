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
        Schema::create('sla_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('slable_type');
            $table->unsignedBigInteger('slable_id');
            $table->foreignId('policy_id')->nullable()->constrained('sla_policies')->nullOnDelete();
            $table->timestamp('first_response_due_at')->nullable();
            $table->timestamp('resolution_due_at')->nullable();
            $table->timestamp('first_responded_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('breached_first_response_at')->nullable();
            $table->timestamp('breached_resolution_at')->nullable();
            $table->string('status')->index();
            $table->timestamps();

            $table->index(['slable_type', 'slable_id']);
            $table->index(['company_id', 'status']);
            $table->index(['first_response_due_at', 'resolution_due_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sla_records');
    }
};
