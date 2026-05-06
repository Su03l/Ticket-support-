<?php

namespace App\Services;

use App\Models\ErrorLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class ErrorLogService
{
    public function record(Throwable $exception, ?Request $request = null): ?ErrorLog
    {
        if (! Schema::hasTable('error_logs')) {
            return null;
        }

        $request ??= request();
        $user = $request->user();
        $errorId = 'ERR-'.Str::upper(Str::random(12));

        $log = ErrorLog::query()->create([
            'company_id' => $user?->company_id,
            'user_id' => $user?->id,
            'error_id' => $errorId,
            'exception_class' => $exception::class,
            'message' => Str::limit($exception->getMessage(), 2000, ''),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'route' => $request->route()?->getName(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_payload' => $this->sanitizePayload($request->except(['password', 'password_confirmation'])),
            'stack_trace' => $exception->getTraceAsString(),
            'environment' => app()->environment(),
        ]);

        $request->attributes->set('error_id', $errorId);

        return $log;
    }

    public function markResolved(ErrorLog $errorLog, User $user, ?string $notes = null): ErrorLog
    {
        $errorLog->forceFill([
            'resolved_at' => now(),
            'resolved_by_id' => $user->id,
            'notes' => $notes,
        ])->save();

        return $errorLog->refresh();
    }

    private function sanitizePayload(array $payload): array
    {
        $blocked = ['password', 'token', 'secret', 'authorization', 'api_key', 'two_factor_secret'];

        foreach ($payload as $key => $value) {
            if (Str::contains(Str::lower((string) $key), $blocked)) {
                $payload[$key] = '[redacted]';
            } elseif (is_array($value)) {
                $payload[$key] = $this->sanitizePayload($value);
            }
        }

        return $payload;
    }
}
