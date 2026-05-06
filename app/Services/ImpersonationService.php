<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ImpersonationService
{
    public function startImpersonation(User $user): void
    {
        // يجب التأكد من الصلاحيات هنا (مثلاً: مدير النظام فقط)
        if (!Auth::user()->hasRole('super_admin')) {
            abort(403, 'Unauthorized action.');
        }

        Session::put('impersonated_by', Auth::id());
        Auth::login($user);
    }
}