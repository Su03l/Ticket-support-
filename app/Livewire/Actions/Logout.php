<?php

namespace App\Livewire\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class Logout
{
    /**
     * Log the current user out of the application.
     */
    public function __invoke()
    {
        if (Session::has('impersonated_by')) {
            $adminId = Session::get('impersonated_by');
            Auth::guard('web')->loginUsingId($adminId);
            Session::forget('impersonated_by');
            return redirect()->route('dashboard');
        }

        Auth::guard('web')->logout();

        Session::invalidate();
        Session::regenerateToken();

        return redirect('/');
    }
}
