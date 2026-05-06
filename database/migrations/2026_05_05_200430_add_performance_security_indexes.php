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
        Schema::table('attachments', function (Blueprint $table): void {
            $table->index(['company_id', 'visibility', 'created_at'], 'attachments_company_visibility_created_idx');
            $table->index(['uploaded_by_id', 'created_at'], 'attachments_uploader_created_idx');
            $table->index(['attachable_type', 'attachable_id'], 'attachments_attachable_idx');
        });

        Schema::table('complaints', function (Blueprint $table): void {
            $table->index(['company_id', 'status', 'created_at'], 'complaints_company_status_created_idx');
            $table->index(['company_id', 'severity', 'created_at'], 'complaints_company_severity_created_idx');
            $table->index(['customer_id', 'created_at'], 'complaints_customer_created_idx');
            $table->index(['assigned_to_id', 'created_at'], 'complaints_assignee_created_idx');
        });

        Schema::table('inquiries', function (Blueprint $table): void {
            $table->index(['company_id', 'status', 'created_at'], 'inquiries_company_status_created_idx');
            $table->index(['customer_id', 'created_at'], 'inquiries_customer_created_idx');
            $table->index(['assigned_to_id', 'created_at'], 'inquiries_assignee_created_idx');
        });

        Schema::table('mailbox_messages', function (Blueprint $table): void {
            $table->index(['recipient_id', 'read_at', 'archived_at'], 'mailbox_recipient_read_archive_idx');
            $table->index(['company_id', 'created_at'], 'mailbox_company_created_idx');
        });

        Schema::table('support_notifications', function (Blueprint $table): void {
            $table->index(['recipient_id', 'read_at', 'created_at'], 'notifications_recipient_read_created_idx');
            $table->index(['company_id', 'created_at'], 'notifications_company_created_idx');
        });

        Schema::table('tickets', function (Blueprint $table): void {
            $table->index(['company_id', 'status', 'created_at'], 'tickets_company_status_created_idx');
            $table->index(['department_id', 'status', 'created_at'], 'tickets_department_status_created_idx');
            $table->index(['customer_id', 'created_at'], 'tickets_customer_created_idx');
            $table->index(['assigned_to_id', 'created_at'], 'tickets_assignee_created_idx');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->index(['company_id', 'status', 'user_type'], 'users_company_status_type_idx');
            $table->index(['department_id', 'status'], 'users_department_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attachments', function (Blueprint $table): void {
            $table->dropIndex('attachments_company_visibility_created_idx');
            $table->dropIndex('attachments_uploader_created_idx');
            $table->dropIndex('attachments_attachable_idx');
        });

        Schema::table('complaints', function (Blueprint $table): void {
            $table->dropIndex('complaints_company_status_created_idx');
            $table->dropIndex('complaints_company_severity_created_idx');
            $table->dropIndex('complaints_customer_created_idx');
            $table->dropIndex('complaints_assignee_created_idx');
        });

        Schema::table('inquiries', function (Blueprint $table): void {
            $table->dropIndex('inquiries_company_status_created_idx');
            $table->dropIndex('inquiries_customer_created_idx');
            $table->dropIndex('inquiries_assignee_created_idx');
        });

        Schema::table('mailbox_messages', function (Blueprint $table): void {
            $table->dropIndex('mailbox_recipient_read_archive_idx');
            $table->dropIndex('mailbox_company_created_idx');
        });

        Schema::table('support_notifications', function (Blueprint $table): void {
            $table->dropIndex('notifications_recipient_read_created_idx');
            $table->dropIndex('notifications_company_created_idx');
        });

        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropIndex('tickets_company_status_created_idx');
            $table->dropIndex('tickets_department_status_created_idx');
            $table->dropIndex('tickets_customer_created_idx');
            $table->dropIndex('tickets_assignee_created_idx');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_company_status_type_idx');
            $table->dropIndex('users_department_status_idx');
        });
    }
};
