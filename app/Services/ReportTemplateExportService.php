<?php

namespace App\Services;

use App\Enums\ReportExportFormat;
use App\Models\ReportTemplate;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class ReportTemplateExportService
{
    public function export(ReportTemplate $template, User $user): Response
    {
        abort_unless($template->company_id === $user->company_id || $user->company_id === null, 403);

        $body = $this->replaceVariables($template, $user);
        $filename = str($template->name)->slug()->append($template->format === ReportExportFormat::Excel ? '.xls' : '.html')->toString();
        $contentType = $template->format === ReportExportFormat::Excel
            ? 'application/vnd.ms-excel; charset=UTF-8'
            : 'text/html; charset=UTF-8';

        return response($body, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function replaceVariables(ReportTemplate $template, User $user): string
    {
        return str($template->body)
            ->replace('{{ company.name }}', e($template->company->name))
            ->replace('{{ user.name }}', e($user->name))
            ->replace('{{ generated_at }}', e(now()->format('Y-m-d H:i')))
            ->toString();
    }
}
