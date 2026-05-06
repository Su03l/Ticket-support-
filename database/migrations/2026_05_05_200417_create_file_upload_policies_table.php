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
        Schema::create('file_upload_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->json('allowed_mime_types');
            $table->unsignedInteger('max_file_size_kb')->default(10240);
            $table->unsignedInteger('max_files_per_request')->nullable();
            $table->boolean('allow_public_attachments')->default(true);
            $table->boolean('allow_internal_attachments')->default(true);
            $table->timestamps();

            $table->unique('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_upload_policies');
    }
};
