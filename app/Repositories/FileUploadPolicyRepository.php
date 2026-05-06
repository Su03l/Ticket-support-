<?php

namespace App\Repositories;

use App\Models\Company;
use App\Models\FileUploadPolicy;
use App\Repositories\Contracts\FileUploadPolicyRepositoryInterface;

class FileUploadPolicyRepository implements FileUploadPolicyRepositoryInterface
{
    public function firstOrCreateForCompany(Company $company, array $defaults): FileUploadPolicy
    {
        return FileUploadPolicy::query()->firstOrCreate([
            'company_id' => $company->id,
        ], $defaults);
    }

    public function update(FileUploadPolicy $policy, array $attributes): FileUploadPolicy
    {
        $policy->update($attributes);

        return $policy->refresh();
    }
}
