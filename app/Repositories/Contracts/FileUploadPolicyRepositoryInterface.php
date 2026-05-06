<?php

namespace App\Repositories\Contracts;

use App\Models\Company;
use App\Models\FileUploadPolicy;

interface FileUploadPolicyRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $defaults
     */
    public function firstOrCreateForCompany(Company $company, array $defaults): FileUploadPolicy;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(FileUploadPolicy $policy, array $attributes): FileUploadPolicy;
}
