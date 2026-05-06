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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->index();
            $table->foreignId('department_id')->nullable()->after('company_id')->index();
            $table->string('user_type')->default('customer')->after('password')->index();
            $table->string('status')->default('active')->after('user_type')->index();
            $table->string('avatar')->nullable()->after('status');
            $table->string('locale', 10)->default('ar')->after('avatar');
            $table->string('theme_preference')->nullable()->after('locale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'company_id',
                'department_id',
                'user_type',
                'status',
                'avatar',
                'locale',
                'theme_preference',
            ]);
        });
    }
};
