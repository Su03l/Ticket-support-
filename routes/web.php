<?php

use App\Enums\UserType;
use App\Http\Controllers\AttachmentDownloadController;
use App\Http\Controllers\ReportTemplateExportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->user_type === UserType::Customer
            ? redirect()->route('portal.dashboard')
            : redirect()->route('dashboard');
    }

    return view('welcome');
})->name('home');

Route::post('language/{locale}', function (Request $request, string $locale) {
    abort_unless(in_array($locale, ['ar', 'en'], true), 404);

    $request->session()->put('locale', $locale);
    app()->setLocale($locale);

    if ($request->user() !== null) {
        $request->user()->forceFill(['locale' => $locale])->save();
    }

    return back();
})->name('language.switch');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('portal', 'pages::portal.dashboard')->name('portal.dashboard');
    Route::livewire('portal/tickets', 'pages::tickets.index')->name('portal.tickets.index');
    Route::livewire('portal/tickets/create', 'pages::tickets.create')->name('portal.tickets.create');
    Route::livewire('portal/tickets/{ticket}', 'pages::tickets.show')->name('portal.tickets.show');
    Route::livewire('portal/complaints', 'pages::complaints.index')->name('portal.complaints.index');
    Route::livewire('portal/complaints/create', 'pages::complaints.create')->name('portal.complaints.create');
    Route::livewire('portal/complaints/{complaint}', 'pages::complaints.show')->name('portal.complaints.show');
    Route::livewire('portal/inquiries', 'pages::inquiries.index')->name('portal.inquiries.index');
    Route::livewire('portal/inquiries/create', 'pages::inquiries.create')->name('portal.inquiries.create');
    Route::livewire('portal/inquiries/{inquiry}', 'pages::inquiries.show')->name('portal.inquiries.show');
    Route::livewire('portal/knowledge-base', 'pages::knowledge-base.index')->name('portal.knowledge-base.index');
    Route::livewire('portal/knowledge-base/{article}', 'pages::knowledge-base.show')->name('portal.knowledge-base.show');
    Route::livewire('portal/faqs', 'pages::faqs.index')->name('portal.faqs.index');
    Route::livewire('mailbox', 'pages::mailbox.index')->name('mailbox.index');
    Route::livewire('mailbox/{message}', 'pages::mailbox.show')->name('mailbox.show');
    Route::livewire('notifications', 'pages::notifications.index')->name('notifications.index');
    Route::livewire('tickets', 'pages::tickets.index')->name('tickets.index');
    Route::livewire('tickets/create', 'pages::tickets.create')->name('tickets.create');
    Route::livewire('tickets/{ticket}', 'pages::tickets.show')->name('tickets.show');
    Route::livewire('complaints', 'pages::complaints.index')->name('complaints.index');
    Route::livewire('complaints/create', 'pages::complaints.create')->name('complaints.create');
    Route::livewire('complaints/{complaint}', 'pages::complaints.show')->name('complaints.show');
    Route::livewire('inquiries', 'pages::inquiries.index')->name('inquiries.index');
    Route::livewire('inquiries/create', 'pages::inquiries.create')->name('inquiries.create');
    Route::livewire('inquiries/{inquiry}', 'pages::inquiries.show')->name('inquiries.show');
    Route::livewire('activity-logs', 'pages::activity-logs.index')->name('activity-logs.index');
    Route::livewire('error-logs', 'pages::error-logs.index')->name('error-logs.index');
    Route::livewire('error-logs/{errorLog}', 'pages::error-logs.show')->name('error-logs.show');
    Route::livewire('reports', 'pages::reports.index')->name('reports.index');
    Route::livewire('reports/kpis', 'pages::reports.kpis')->name('reports.kpis');
    Route::livewire('reports/templates', 'pages::reports.templates')->name('reports.templates');
    Route::get('reports/templates/{template}/export', ReportTemplateExportController::class)->name('reports.templates.export');
    Route::livewire('files', 'pages::file-manager.index')->name('files.index');
    Route::livewire('companies', 'pages::companies.index')->name('companies.index');
    Route::livewire('departments', 'pages::departments.index')->name('departments.index');
    Route::livewire('users', 'pages::users.index')->name('users.index');
    Route::livewire('roles', 'pages::roles.index')->name('roles.index');
    Route::livewire('canned-responses', 'pages::canned-responses.index')->name('canned-responses.index');
    Route::livewire('knowledge-base', 'pages::knowledge-base.index')->name('knowledge-base.index');
    Route::livewire('knowledge-base/{article}', 'pages::knowledge-base.show')->name('knowledge-base.show');
    Route::livewire('faqs', 'pages::faqs.index')->name('faqs.index');
    Route::livewire('custom-fields', 'pages::custom-fields.index')->name('custom-fields.index');
    Route::get('attachments/{attachment}/download', AttachmentDownloadController::class)->name('attachments.download');
});

require __DIR__.'/settings.php';
