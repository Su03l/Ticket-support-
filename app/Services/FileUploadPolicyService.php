<?php

namespace App\Services;

use App\Enums\AttachmentVisibility;
use App\Models\Company;
use App\Models\FileUploadPolicy;
use App\Repositories\Contracts\FileUploadPolicyRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class FileUploadPolicyService
{
    public function __construct(
        private FileUploadPolicyRepositoryInterface $policies,
    ) {}

    public function policyFor(Company $company): FileUploadPolicy
    {
        return $this->policies->firstOrCreateForCompany($company, $this->defaults());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(FileUploadPolicy $policy, array $attributes): FileUploadPolicy
    {
        $updated = $this->policies->update($policy, [
            'allowed_mime_types' => $this->normalizeMimeTypes($attributes['allowed_mime_types'] ?? []),
            'max_file_size_kb' => (int) $attributes['max_file_size_kb'],
            'max_files_per_request' => filled($attributes['max_files_per_request'] ?? null) ? (int) $attributes['max_files_per_request'] : null,
            'allow_public_attachments' => (bool) ($attributes['allow_public_attachments'] ?? false),
            'allow_internal_attachments' => (bool) ($attributes['allow_internal_attachments'] ?? false),
        ]);

        activity()->performedOn($updated)->event('file_policy.updated')->log('File upload policy updated');

        return $updated;
    }

    /**
     * @param  array<int, UploadedFile>  $files
     *
     * @throws ValidationException
     */
    public function ensureFilesAllowed(Company $company, array $files, AttachmentVisibility $visibility): void
    {
        $policy = $this->policyFor($company);

        if ($policy->max_files_per_request !== null && count($files) > $policy->max_files_per_request) {
            throw ValidationException::withMessages([
                'attachments' => __('Too many files were attached.'),
            ]);
        }

        foreach ($files as $file) {
            $this->ensureFileAllowed($policy, $file, $visibility);
        }
    }

    /**
     * @throws ValidationException
     */
    public function ensureFileAllowed(FileUploadPolicy $policy, UploadedFile $file, AttachmentVisibility $visibility): void
    {
        if ($visibility === AttachmentVisibility::Public && ! $policy->allow_public_attachments) {
            throw ValidationException::withMessages([
                'attachments' => __('Public attachments are disabled for this company.'),
            ]);
        }

        if ($visibility === AttachmentVisibility::Internal && ! $policy->allow_internal_attachments) {
            throw ValidationException::withMessages([
                'attachments' => __('Internal attachments are disabled for this company.'),
            ]);
        }

        if (! in_array($file->getMimeType(), $policy->allowed_mime_types ?? [], true)) {
            throw ValidationException::withMessages([
                'attachments' => __('This file type is not allowed.'),
            ]);
        }

        if (($file->getSize() / 1024) > $policy->max_file_size_kb) {
            throw ValidationException::withMessages([
                'attachments' => __('This file exceeds the company upload limit.'),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function defaults(): array
    {
        return [
            'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/webp', 'application/pdf', 'text/plain'],
            'max_file_size_kb' => 10240,
            'max_files_per_request' => 5,
            'allow_public_attachments' => true,
            'allow_internal_attachments' => true,
        ];
    }

    /**
     * @param  array<int, string>|string  $mimeTypes
     * @return array<int, string>
     */
    private function normalizeMimeTypes(array|string $mimeTypes): array
    {
        if (is_string($mimeTypes)) {
            $mimeTypes = preg_split('/[\s,]+/', $mimeTypes) ?: [];
        }

        return collect($mimeTypes)
            ->map(fn (string $mimeType): string => trim($mimeType))
            ->filter(fn (string $mimeType): bool => $mimeType !== '' && str_contains($mimeType, '/'))
            ->unique()
            ->values()
            ->all();
    }
}
