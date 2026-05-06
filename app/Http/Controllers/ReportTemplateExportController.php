<?php

namespace App\Http\Controllers;

use App\Models\ReportTemplate;
use App\Services\ReportTemplateExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportTemplateExportController extends Controller
{
    public function __invoke(Request $request, ReportTemplate $template, ReportTemplateExportService $exports): Response
    {
        abort_unless($request->user()?->can('reports.export'), 403);

        return $exports->export($template, $request->user());
    }
}
