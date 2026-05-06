<?php

namespace App\Policies;

use App\Models\Faq;
use App\Models\User;

class FaqPolicy
{
    public function viewAny(User $user): bool { return $user->can('faq.view'); }
    public function create(User $user): bool { return $user->can('faq.create'); }
    public function update(User $user, Faq $faq): bool { return $user->can('faq.update') && $faq->company_id === $user->company_id; }
    public function delete(User $user, Faq $faq): bool { return $user->can('faq.delete') && $faq->company_id === $user->company_id; }
}
