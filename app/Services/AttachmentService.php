<?php

namespace App\Services;

use App\Enums\AttachmentVisibility;
use App\Models\Attachment;
use App\Models\User;
use App\Repositories\Contracts\AttachmentRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class AttachmentService
{
    public function __construct(
        private AttachmentRepositoryInterface $attachments,
        private FileUploadPolicyService $filePolicies,
    ) {}

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function ensureFilesAllowed(Model $attachable, array $files, AttachmentVisibility $visibility): void
    {
        $company = $attachable->company;

        if ($company === null) {
            throw new InvalidArgumentException('Attachments must belong to a company.');
        }

        $this->filePolicies->ensureFilesAllowed($company, $files, $visibility);
    }

    public function storeFor(Model $attachable, User $uploadedBy, UploadedFile $file, AttachmentVisibility $visibility = AttachmentVisibility::Public): Attachment
    {
        $this->ensureFilesAllowed($attachable, [$file], $visibility);

        $companyId = $attachable->company_id;
        $storedName = $file->hashName();
        $path = $file->storeAs("attachments/{$companyId}", $storedName, 'local');

        $attachment = $this->attachments->create([
            'company_id' => $companyId,
            'uploaded_by_id' => $uploadedBy->id,
            'attachable_type' => $attachable::class,
            'attachable_id' => $attachable->id,
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'path' => $path,
            'disk' => 'local',
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'visibility' => $visibility,
        ]);

        activity()->performedOn($attachment)->causedBy($uploadedBy)->event('attachment.uploaded')->log('Attachment uploaded');

        return $attachment;
    }

    public function delete(Attachment $attachment, User $deletedBy): void
    {
        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();

        activity()->performedOn($attachment)->causedBy($deletedBy)->event('attachment.deleted')->log('Attachment deleted');
    }
}
