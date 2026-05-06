<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;

class CustomerDashboard extends Component
{
    public function render()
    {
        $user = Auth::user();
        
        // إحصائيات التذاكر الخاصة بالعميل
        $stats = [
            'total' => Ticket::where('user_id', $user->id)->count(),
            'open' => Ticket::where('user_id', $user->id)->whereIn('status', ['new', 'in_progress', 'pending'])->count(),
            'closed' => Ticket::where('user_id', $user->id)->where('status', 'closed')->count(),
        ];

        // جلب آخر 5 تذاكر لعرضها
        $recentTickets = Ticket::where('user_id', $user->id)->latest()->take(5)->get();

        return view('livewire.customer.customer-dashboard', compact('stats', 'recentTickets'))
            ->title(__('بوابة العملاء - الرئيسية'));
    }
}