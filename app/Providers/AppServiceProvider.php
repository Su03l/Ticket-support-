<?php

namespace App\Providers;

use App\Events\TicketAssigned;
use App\Events\TicketCreated;
use App\Events\TicketReplied;
use App\Events\ComplaintCreated;
use App\Events\ComplaintEscalated;
use App\Events\ComplaintStatusChanged;
use App\Events\InquiryAnswered;
use App\Events\InquiryAssigned;
use App\Events\InquiryCreated;
use App\Listeners\SendComplaintCreatedNotification;
use App\Listeners\SendComplaintEscalationMailboxMessage;
use App\Listeners\SendComplaintStatusNotification;
use App\Listeners\SendInquiryAnsweredNotification;
use App\Listeners\SendInquiryAssignedMailboxMessage;
use App\Listeners\SendInquiryCreatedNotification;
use App\Listeners\SendTicketAssignedMailboxMessage;
use App\Listeners\SendTicketCreatedNotification;
use App\Listeners\SendTicketReplyNotification;
use App\Models\Attachment;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Complaint;
use App\Models\CannedResponse;
use App\Models\CustomField;
use App\Models\Department;
use App\Models\ErrorLog;
use App\Models\Faq;
use App\Models\FileUploadPolicy;
use App\Models\Inquiry;
use App\Models\KnowledgeBaseArticle;
use App\Models\MailboxMessage;
use App\Models\SupportNotification;
use App\Models\Ticket;
use App\Models\TicketRating;
use App\Models\User;
use App\Models\UserInvitation;
use App\Policies\AttachmentPolicy;
use App\Policies\CompanyPolicy;
use App\Policies\CompanySettingPolicy;
use App\Policies\ComplaintPolicy;
use App\Policies\CannedResponsePolicy;
use App\Policies\CustomFieldPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\ErrorLogPolicy;
use App\Policies\FaqPolicy;
use App\Policies\FileUploadPolicyPolicy;
use App\Policies\InquiryPolicy;
use App\Policies\KnowledgeBaseArticlePolicy;
use App\Policies\MailboxMessagePolicy;
use App\Policies\RolePolicy;
use App\Policies\SupportNotificationPolicy;
use App\Policies\TicketPolicy;
use App\Policies\TicketRatingPolicy;
use App\Policies\UserInvitationPolicy;
use App\Policies\UserPolicy;
use App\Repositories\AttachmentRepository;
use App\Repositories\CompanyRepository;
use App\Repositories\ComplaintReplyRepository;
use App\Repositories\ComplaintRepository;
use App\Repositories\ComplaintStatusHistoryRepository;
use App\Repositories\Contracts\AttachmentRepositoryInterface;
use App\Repositories\CannedResponseRepository;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use App\Repositories\Contracts\CannedResponseRepositoryInterface;
use App\Repositories\Contracts\ComplaintReplyRepositoryInterface;
use App\Repositories\Contracts\ComplaintRepositoryInterface;
use App\Repositories\Contracts\ComplaintStatusHistoryRepositoryInterface;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Repositories\Contracts\FileUploadPolicyRepositoryInterface;
use App\Repositories\Contracts\InquiryReplyRepositoryInterface;
use App\Repositories\Contracts\InquiryRepositoryInterface;
use App\Repositories\Contracts\InquiryStatusHistoryRepositoryInterface;
use App\Repositories\Contracts\MailboxRepositoryInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\ReportRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use App\Repositories\Contracts\TicketAssignmentRepositoryInterface;
use App\Repositories\Contracts\TicketCommentRepositoryInterface;
use App\Repositories\Contracts\TicketReplyRepositoryInterface;
use App\Repositories\Contracts\TicketRepositoryInterface;
use App\Repositories\Contracts\TicketStatusHistoryRepositoryInterface;
use App\Repositories\Contracts\TicketTransferRepositoryInterface;
use App\Repositories\Contracts\TicketRatingRepositoryInterface;
use App\Repositories\Contracts\UserInvitationRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\DepartmentRepository;
use App\Repositories\FileUploadPolicyRepository;
use App\Repositories\InquiryReplyRepository;
use App\Repositories\InquiryRepository;
use App\Repositories\InquiryStatusHistoryRepository;
use App\Repositories\MailboxRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\PlanRepository;
use App\Repositories\ReportRepository;
use App\Repositories\RoleRepository;
use App\Repositories\SubscriptionRepository;
use App\Repositories\TicketAssignmentRepository;
use App\Repositories\TicketCommentRepository;
use App\Repositories\TicketReplyRepository;
use App\Repositories\TicketRepository;
use App\Repositories\TicketStatusHistoryRepository;
use App\Repositories\TicketTransferRepository;
use App\Repositories\TicketRatingRepository;
use App\Repositories\UserInvitationRepository;
use App\Repositories\UserRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Activitylog\Models\Activity;
use App\Policies\ActivityLogPolicy;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CompanyRepositoryInterface::class, CompanyRepository::class);
        $this->app->bind(DepartmentRepositoryInterface::class, DepartmentRepository::class);
        $this->app->bind(FileUploadPolicyRepositoryInterface::class, FileUploadPolicyRepository::class);
        $this->app->bind(MailboxRepositoryInterface::class, MailboxRepository::class);
        $this->app->bind(NotificationRepositoryInterface::class, NotificationRepository::class);
        $this->app->bind(PlanRepositoryInterface::class, PlanRepository::class);
        $this->app->bind(RoleRepositoryInterface::class, RoleRepository::class);
        $this->app->bind(SubscriptionRepositoryInterface::class, SubscriptionRepository::class);
        $this->app->bind(UserInvitationRepositoryInterface::class, UserInvitationRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(AttachmentRepositoryInterface::class, AttachmentRepository::class);
        $this->app->bind(ComplaintRepositoryInterface::class, ComplaintRepository::class);
        $this->app->bind(ComplaintReplyRepositoryInterface::class, ComplaintReplyRepository::class);
        $this->app->bind(ComplaintStatusHistoryRepositoryInterface::class, ComplaintStatusHistoryRepository::class);
        $this->app->bind(InquiryRepositoryInterface::class, InquiryRepository::class);
        $this->app->bind(InquiryReplyRepositoryInterface::class, InquiryReplyRepository::class);
        $this->app->bind(InquiryStatusHistoryRepositoryInterface::class, InquiryStatusHistoryRepository::class);
        $this->app->bind(TicketRatingRepositoryInterface::class, TicketRatingRepository::class);
        $this->app->bind(CannedResponseRepositoryInterface::class, CannedResponseRepository::class);
        $this->app->bind(ReportRepositoryInterface::class, ReportRepository::class);
        $this->app->bind(TicketRepositoryInterface::class, TicketRepository::class);
        $this->app->bind(TicketReplyRepositoryInterface::class, TicketReplyRepository::class);
        $this->app->bind(TicketCommentRepositoryInterface::class, TicketCommentRepository::class);
        $this->app->bind(TicketAssignmentRepositoryInterface::class, TicketAssignmentRepository::class);
        $this->app->bind(TicketTransferRepositoryInterface::class, TicketTransferRepository::class);
        $this->app->bind(TicketStatusHistoryRepositoryInterface::class, TicketStatusHistoryRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(CompanySetting::class, CompanySettingPolicy::class);
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(Attachment::class, AttachmentPolicy::class);
        Gate::policy(Activity::class, ActivityLogPolicy::class);
        Gate::policy(CannedResponse::class, CannedResponsePolicy::class);
        Gate::policy(Complaint::class, ComplaintPolicy::class);
        Gate::policy(CustomField::class, CustomFieldPolicy::class);
        Gate::policy(ErrorLog::class, ErrorLogPolicy::class);
        Gate::policy(Faq::class, FaqPolicy::class);
        Gate::policy(FileUploadPolicy::class, FileUploadPolicyPolicy::class);
        Gate::policy(Inquiry::class, InquiryPolicy::class);
        Gate::policy(KnowledgeBaseArticle::class, KnowledgeBaseArticlePolicy::class);
        Gate::policy(MailboxMessage::class, MailboxMessagePolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(SupportNotification::class, SupportNotificationPolicy::class);
        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(TicketRating::class, TicketRatingPolicy::class);
        Gate::policy(UserInvitation::class, UserInvitationPolicy::class);

        Event::listen(ComplaintCreated::class, SendComplaintCreatedNotification::class);
        Event::listen(ComplaintStatusChanged::class, SendComplaintStatusNotification::class);
        Event::listen(ComplaintEscalated::class, SendComplaintEscalationMailboxMessage::class);
        Event::listen(InquiryCreated::class, SendInquiryCreatedNotification::class);
        Event::listen(InquiryAnswered::class, SendInquiryAnsweredNotification::class);
        Event::listen(InquiryAssigned::class, SendInquiryAssignedMailboxMessage::class);
        Event::listen(TicketCreated::class, SendTicketCreatedNotification::class);
        Event::listen(TicketAssigned::class, SendTicketAssignedMailboxMessage::class);
        Event::listen(TicketReplied::class, SendTicketReplyNotification::class);
    }
}
