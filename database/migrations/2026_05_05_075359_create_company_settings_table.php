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
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('primary_color', 20)->default('#2563eb');
            $table->string('secondary_color', 20)->default('#0f172a');
            $table->string('sidebar_color', 20)->default('#ffffff');
            $table->boolean('login_branding_enabled')->default(true);
            $table->string('login_heading')->nullable();
            $table->string('login_subheading')->nullable();
            $table->string('default_locale', 10)->default('ar');
            $table->string('theme_mode')->default('system');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
