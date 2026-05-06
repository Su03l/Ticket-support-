<?php

namespace App\Livewire\Customer\Tickets;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Department;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;

class CreateTicket extends Component
{
    use WithFileUploads;

    public $subject;
    public $department_id;
    public $priority = 'low';
    public $description;
    public $attachments = [];

    public function save()
    {
        $this->validate([
            'subject' => 'required|string|max:255',
            'department_id' => 'required|exists:departments,id',
            'priority' => 'required|in:low,medium,high,urgent',
            'description' => 'required|string',
            'attachments.*' => 'nullable|file|max:5120', // بحد أقصى 5 ميجابايت للملف
        ]);

        $ticket = Ticket::create([
            'company_id' => Auth::user()->company_id, // ربط بالـ Tenant
            'user_id' => Auth::id(),
            'department_id' => $this->department_id,
            'subject' => $this->subject,
            'priority' => $this->priority,
            'status' => 'new',
            'description' => $this->description,
        ]);

        // TODO: حفظ المرفقات إن وجدت باستخدام سياسات رفع الملفات الآمنة لديك

        // إشعار نجاح
        session()->flash('status', __('تم إنشاء التذكرة بنجاح، سيقوم فريق الدعم بالرد عليك قريباً.'));
        
        return redirect()->route('customer.dashboard');
    }

    public function render()
    {
        $departments = Department::where('is_active', true)->get();
        
        return view('livewire.customer.tickets.create-ticket', compact('departments'))
            ->title(__('إنشاء تذكرة جديدة'));
    }
}