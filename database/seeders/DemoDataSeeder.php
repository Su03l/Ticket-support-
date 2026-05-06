<?php

namespace Database\Seeders;

use App\Enums\ArticleStatus;
use App\Enums\ArticleVisibility;
use App\Enums\AttachmentVisibility;
use App\Enums\BillingCycle;
use App\Enums\CompanyStatus;
use App\Enums\ComplaintSeverity;
use App\Enums\ComplaintStatus;
use App\Enums\InquiryStatus;
use App\Enums\MailboxMessageType;
use App\Enums\NpsCategory;
use App\Enums\ReportExportFormat;
use App\Enums\ReplyVisibility;
use App\Enums\ScheduledReportFrequency;
use App\Enums\SubscriptionStatus;
use App\Enums\TicketSource;
use App\Enums\TicketStatus;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\Attachment;
use App\Models\CannedResponse;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Complaint;
use App\Models\CustomerSatisfactionSurvey;
use App\Models\Department;
use App\Models\EmployeeKpiTarget;
use App\Models\Faq;
use App\Models\FileUploadPolicy;
use App\Models\Inquiry;
use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use App\Models\MailboxMessage;
use App\Models\Plan;
use App\Models\ReportTemplate;
use App\Models\ScheduledReport;
use App\Models\Subscription;
use App\Models\SupportNotification;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketComment;
use App\Models\TicketPriority;
use App\Models\TicketRating;
use App\Models\TicketReply;
use App\Models\TicketTimeEntry;
use App\Models\User;
use App\Models\WorkingHour;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $plan = Plan::query()->updateOrCreate([
            'slug' => 'enterprise-support',
        ], [
            'name' => 'Enterprise Support',
            'price' => 499,
            'billing_cycle' => BillingCycle::Monthly,
            'max_users' => 150,
            'max_departments' => 12,
            'max_tickets_per_month' => 5000,
            'is_active' => true,
        ]);

        $company = Company::query()->updateOrCreate([
            'slug' => 'riyadh-operations',
        ], [
            'name' => 'Riyadh Operations Co.',
            'email' => 'support@riyadh-ops.example',
            'phone' => '+966 11 555 0101',
            'website' => 'https://riyadh-ops.example',
            'status' => CompanyStatus::Active,
            'plan_id' => $plan->id,
        ]);

        CompanySetting::query()->firstOrCreate(['company_id' => $company->id], [
            'primary_color' => '#0f766e',
            'secondary_color' => '#172554',
            'sidebar_color' => '#ffffff',
            'login_branding_enabled' => true,
            'login_heading' => 'بوابة دعم العملاء',
            'login_subheading' => 'فريقنا جاهز لمساعدتك',
            'default_locale' => 'ar',
            'theme_mode' => 'system',
        ]);

        FileUploadPolicy::query()->firstOrCreate(['company_id' => $company->id], [
            'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/webp', 'application/pdf', 'text/plain'],
            'max_file_size_kb' => 10240,
            'max_files_per_request' => 5,
            'allow_public_attachments' => true,
            'allow_internal_attachments' => true,
        ]);

        Subscription::query()->updateOrCreate([
            'company_id' => $company->id,
            'plan_id' => $plan->id,
        ], [
            'status' => SubscriptionStatus::Active,
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->addMonths(10),
        ]);

        $departments = collect([
            'Technical Support' => 'حل المشاكل التقنية والتكاملات.',
            'Customer Success' => 'متابعة العملاء ورفع جودة التجربة.',
            'Billing Support' => 'الفواتير والاشتراكات والمدفوعات.',
        ])->map(fn (string $description, string $name): Department => Department::query()->updateOrCreate([
            'company_id' => $company->id,
            'slug' => Str::slug($name),
        ], [
            'name' => $name,
            'status' => \App\Enums\DepartmentStatus::Active,
            'description' => $description,
        ]));

        $users = $this->users($company, $departments);

        $departments->each(function (Department $department) use ($users): void {
            $manager = $users['manager'];
            $deputy = $users['deputy'];

            $department->forceFill([
                'manager_id' => $manager->id,
                'deputy_id' => $deputy->id,
            ])->save();
        });

        $priorities = collect([
            ['Low', 1, '#64748b', 240, 2880],
            ['Normal', 2, '#0ea5e9', 120, 1440],
            ['High', 3, '#f59e0b', 60, 480],
            ['Urgent', 4, '#dc2626', 15, 120],
        ])->map(fn (array $priority): TicketPriority => TicketPriority::query()->updateOrCreate([
            'company_id' => $company->id,
            'slug' => Str::slug($priority[0]),
        ], [
            'name' => $priority[0],
            'level' => $priority[1],
            'color' => $priority[2],
            'response_time_minutes' => $priority[3],
            'resolution_time_minutes' => $priority[4],
            'is_active' => true,
        ]));

        $categories = collect(['Login issues', 'System outage', 'Billing question', 'Feature request'])->map(fn (string $name): TicketCategory => TicketCategory::query()->updateOrCreate([
            'company_id' => $company->id,
            'slug' => Str::slug($name),
        ], [
            'department_id' => $departments->first()->id,
            'name' => $name,
            'description' => 'تصنيف مستخدم في بيانات العرض.',
            'is_active' => true,
        ]));

        $tickets = $this->tickets($company, $departments, $priorities, $categories, $users);
        $this->knowledge($company, $departments->first(), $users['agent'], $users['customer']);
        $this->communications($company, $tickets, $users);
        $this->reports($company, $users['admin'], $users['agent']);
        $this->workingHours($company);
        $this->tenantIntegrations($company);
    }

    /**
     * @return array<string, User>
     */
    private function users(Company $company, \Illuminate\Support\Collection $departments): array
    {
        $password = Hash::make('password');
        $departmentList = $departments->values();

        $records = [
            'admin' => ['Maha Alotaibi', 'maha.admin@example.com', UserType::CompanyAdmin, null],
            'manager' => ['Fahad Alharbi', 'fahad.manager@example.com', UserType::DepartmentManager, $departmentList->first()->id],
            'deputy' => ['Noura Alqahtani', 'noura.deputy@example.com', UserType::DepartmentDeputy, $departmentList->first()->id],
            'agent' => ['Ahmed Saleh', 'ahmed.agent@example.com', UserType::SupportAgent, $departmentList->first()->id],
            'agent2' => ['Sara Mansour', 'sara.agent@example.com', UserType::SupportAgent, $departmentList->get(1)->id],
            'customer' => ['Khalid Customer', 'khalid.customer@example.com', UserType::Customer, null],
        ];

        return collect($records)->mapWithKeys(function (array $record, string $key) use ($company, $password): array {
            $user = User::query()->updateOrCreate([
                'email' => $record[1],
            ], [
                'name' => $record[0],
                'password' => $password,
                'email_verified_at' => now(),
                'company_id' => $company->id,
                'department_id' => $record[3],
                'user_type' => $record[2],
                'status' => UserStatus::Active,
                'locale' => 'ar',
            ]);

            $user->syncRoles([$record[2]->value]);

            return [$key => $user];
        })->all();
    }

    private function tickets(Company $company, \Illuminate\Support\Collection $departments, \Illuminate\Support\Collection $priorities, \Illuminate\Support\Collection $categories, array $users): \Illuminate\Support\Collection
    {
        $data = [
            ['TCK-202605-1001', 'تعذر تسجيل الدخول بعد تحديث النظام', TicketStatus::Open, $priorities->get(2), $users['agent']],
            ['TCK-202605-1002', 'بطء في لوحة التقارير الشهرية', TicketStatus::InProgress, $priorities->get(1), $users['agent']],
            ['TCK-202605-1003', 'طلب تعديل بيانات الفاتورة', TicketStatus::WaitingCustomer, $priorities->get(0), $users['agent2']],
            ['TCK-202605-1004', 'انقطاع تنبيهات البريد الداخلي', TicketStatus::Resolved, $priorities->get(3), $users['agent']],
            ['TCK-202605-1005', 'استفسار عن صلاحيات مدير القسم', TicketStatus::Closed, $priorities->get(1), $users['agent2']],
        ];

        return collect($data)->map(function (array $row, int $index) use ($company, $departments, $categories, $users): Ticket {
            $ticket = Ticket::query()->updateOrCreate([
                'ticket_number' => $row[0],
            ], [
                'company_id' => $company->id,
                'department_id' => $departments->values()->get($index % $departments->count())->id,
                'customer_id' => $users['customer']->id,
                'assigned_to_id' => $row[4]->id,
                'category_id' => $categories->values()->get($index % $categories->count())->id,
                'priority_id' => $row[3]->id,
                'title' => $row[1],
                'description' => 'تفاصيل واقعية للتذكرة تظهر في الواجهة وتساعد على تجربة النظام بدون صفحات فارغة.',
                'status' => $row[2],
                'source' => TicketSource::Web,
                'resolved_at' => in_array($row[2], [TicketStatus::Resolved, TicketStatus::Closed], true) ? now()->subDays($index + 1) : null,
                'closed_at' => $row[2] === TicketStatus::Closed ? now()->subDay() : null,
            ]);

            TicketReply::query()->updateOrCreate([
                'company_id' => $company->id,
                'ticket_id' => $ticket->id,
                'body' => 'تم استلام الطلب وسنراجع التفاصيل مع الفريق المختص.',
            ], [
                'user_id' => $row[4]->id,
                'visibility' => ReplyVisibility::Public,
            ]);

            $comment = TicketComment::query()->updateOrCreate([
                'company_id' => $company->id,
                'ticket_id' => $ticket->id,
                'body' => 'ملاحظة داخلية: يرجى مراجعة الحالة مع @ahmed قبل الإغلاق.',
            ], [
                'user_id' => $users['manager']->id,
            ]);

            TicketTimeEntry::query()->updateOrCreate([
                'ticket_id' => $ticket->id,
                'user_id' => $row[4]->id,
            ], [
                'company_id' => $company->id,
                'started_at' => now()->subHours($index + 2),
                'stopped_at' => now()->subHours($index + 1),
                'duration_seconds' => 3600,
                'note' => 'عمل فعلي على التذكرة.',
            ]);

            if ($index === 0) {
                Storage::disk('local')->put('demo/ticket-summary.txt', 'Demo ticket attachment');
                Attachment::query()->updateOrCreate([
                    'company_id' => $company->id,
                    'attachable_type' => TicketComment::class,
                    'attachable_id' => $comment->id,
                    'original_name' => 'ticket-summary.txt',
                ], [
                    'uploaded_by_id' => $users['manager']->id,
                    'stored_name' => 'ticket-summary.txt',
                    'path' => 'demo/ticket-summary.txt',
                    'disk' => 'local',
                    'mime_type' => 'text/plain',
                    'size' => 22,
                    'visibility' => AttachmentVisibility::Internal,
                ]);
            }

            if ($ticket->status === TicketStatus::Closed) {
                TicketRating::query()->updateOrCreate([
                    'ticket_id' => $ticket->id,
                ], [
                    'company_id' => $company->id,
                    'customer_id' => $users['customer']->id,
                    'rating' => 5,
                    'feedback' => 'خدمة ممتازة وسريعة.',
                    'submitted_at' => now()->subHours(8),
                ]);

                CustomerSatisfactionSurvey::query()->updateOrCreate([
                    'ticket_id' => $ticket->id,
                ], [
                    'company_id' => $company->id,
                    'customer_id' => $users['customer']->id,
                    'agent_id' => $ticket->assigned_to_id,
                    'department_id' => $ticket->department_id,
                    'csat_score' => 5,
                    'nps_score' => 9,
                    'nps_category' => NpsCategory::Promoter,
                    'feedback' => 'سأوصي بالخدمة لفريق آخر.',
                    'sent_at' => now()->subDay(),
                    'submitted_at' => now()->subHours(6),
                ]);
            }

            return $ticket;
        });
    }

    private function knowledge(Company $company, Department $department, User $author, User $customer): void
    {
        $category = KnowledgeBaseCategory::query()->updateOrCreate([
            'company_id' => $company->id,
            'slug' => 'getting-started',
        ], [
            'name' => 'Getting Started',
            'description' => 'مقالات البداية السريعة',
            'is_active' => true,
        ]);

        KnowledgeBaseArticle::query()->updateOrCreate([
            'company_id' => $company->id,
            'slug' => 'reset-password-guide',
        ], [
            'category_id' => $category->id,
            'author_id' => $author->id,
            'title' => 'دليل إعادة تعيين كلمة المرور',
            'excerpt' => 'خطوات مختصرة لمساعدة العملاء.',
            'content' => 'افتح صفحة الدخول ثم اختر نسيت كلمة المرور واتبع التعليمات.',
            'visibility' => ArticleVisibility::Public,
            'status' => ArticleStatus::Published,
            'published_at' => now()->subDays(5),
        ]);

        CannedResponse::query()->updateOrCreate([
            'company_id' => $company->id,
            'title' => 'طلب معلومات إضافية',
        ], [
            'department_id' => $department->id,
            'created_by_id' => $author->id,
            'body' => 'نحتاج بعض المعلومات الإضافية حتى نكمل معالجة طلبك.',
            'category' => 'General',
            'is_active' => true,
        ]);

        collect([
            ['كيف أتابع حالة التذكرة؟', 'من صفحة التذاكر يمكنك فتح التذكرة ومتابعة المحادثة والحالة.'],
            ['هل أستطيع إرفاق ملف؟', 'نعم، يمكن رفع الملفات حسب سياسة الشركة وحجم الملف المسموح.'],
            ['متى يتم إرسال استبيان الرضا؟', 'يتم إرساله بعد إغلاق التذكرة مباشرة.'],
        ])->each(fn (array $faq, int $index) => Faq::query()->updateOrCreate([
            'company_id' => $company->id,
            'question' => $faq[0],
        ], [
            'answer' => $faq[1],
            'category' => 'General',
            'is_active' => true,
            'sort_order' => $index + 1,
        ]));

        Complaint::query()->updateOrCreate([
            'company_id' => $company->id,
            'complaint_number' => 'CMP-202605-1001',
        ], [
            'department_id' => $department->id,
            'customer_id' => $customer->id,
            'assigned_to_id' => $author->id,
            'title' => 'تأخر في الرد على طلب سابق',
            'description' => 'شكوى تجريبية تظهر في لوحة الشكاوى.',
            'severity' => ComplaintSeverity::Medium,
            'status' => ComplaintStatus::New,
        ]);

        Inquiry::query()->updateOrCreate([
            'company_id' => $company->id,
            'inquiry_number' => 'INQ-202605-1001',
        ], [
            'department_id' => $department->id,
            'customer_id' => $customer->id,
            'assigned_to_id' => $author->id,
            'subject' => 'استفسار عن باقة الدعم',
            'body' => 'استفسار تجريبي لإظهار صفحة الاستفسارات.',
            'status' => InquiryStatus::Open,
        ]);
    }

    private function tenantIntegrations(Company $company): void
    {
        \App\Models\Webhook::query()->updateOrCreate([
            'company_id' => $company->id,
            'name' => 'Slack Notifications',
        ], [
            'url' => '',
            'secret' => Str::random(32),
            'events' => ['ticket.created', 'ticket.resolved', 'sla.breached'],
            'is_active' => true,
        ]);

        \Illuminate\Support\Facades\DB::table('customer_organizations')->updateOrInsert([
            'company_id' => $company->id,
            'name' => 'Acme Corp',
        ], [
            'domain' => 'acme.com',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function communications(Company $company, \Illuminate\Support\Collection $tickets, array $users): void
    {
        SupportNotification::query()->updateOrCreate([
            'recipient_id' => $users['agent']->id,
            'type' => 'ticket.assigned',
            'title' => 'تم تعيين تذكرة جديدة',
        ], [
            'company_id' => $company->id,
            'body' => 'تم تعيين تذكرة ذات أولوية عالية لك.',
            'link' => route('tickets.show', $tickets->first()),
            'data' => ['ticket_id' => $tickets->first()->id],
        ]);

        MailboxMessage::query()->updateOrCreate([
            'company_id' => $company->id,
            'recipient_id' => $users['agent']->id,
            'subject' => 'تنبيه إداري بخصوص التصعيد',
        ], [
            'sender_id' => $users['manager']->id,
            'body' => 'يرجى مراجعة التذاكر المفتوحة قبل نهاية الدوام.',
            'type' => MailboxMessageType::AdminNotice,
            'related_type' => Ticket::class,
            'related_id' => $tickets->first()->id,
        ]);
    }

    private function reports(Company $company, User $admin, User $agent): void
    {
        ScheduledReport::query()->updateOrCreate([
            'company_id' => $company->id,
            'name' => 'تقرير أداء الدعم الأسبوعي',
        ], [
            'created_by_id' => $admin->id,
            'frequency' => ScheduledReportFrequency::Weekly,
            'format' => ReportExportFormat::Excel,
            'recipients' => [$admin->email],
            'filters' => [],
            'next_run_at' => now()->next('monday')->setTime(8, 0),
            'is_active' => true,
        ]);

        ReportTemplate::query()->updateOrCreate([
            'company_id' => $company->id,
            'name' => 'قالب تقرير عربي',
        ], [
            'created_by_id' => $admin->id,
            'format' => ReportExportFormat::Pdf,
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'body' => '<section dir="rtl"><h1>تقرير {{ company.name }}</h1><p>{{ generated_at }}</p><p>المسؤول: {{ user.name }}</p></section>',
            'data_sources' => ['tickets', 'csat'],
            'is_active' => true,
        ]);

        EmployeeKpiTarget::query()->updateOrCreate([
            'company_id' => $company->id,
            'user_id' => $agent->id,
            'month' => now()->month,
            'year' => now()->year,
        ], [
            'managed_by_id' => $admin->id,
            'tickets_resolved_target' => 25,
            'first_response_minutes_target' => 30,
            'csat_target' => 4.5,
            'quality_score_target' => 92,
        ]);
    }

    private function workingHours(Company $company): void
    {
        foreach (range(0, 6) as $day) {
            WorkingHour::query()->updateOrCreate([
                'company_id' => $company->id,
                'day_of_week' => $day,
            ], [
                'starts_at' => '08:00',
                'ends_at' => '17:00',
                'is_working_day' => ! in_array($day, [5, 6], true),
            ]);
        }
    }
}
