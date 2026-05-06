<?php

namespace App\Services;

use App\Models\Complaint;

class ComplaintNumberGenerator
{
    public function generate(): string
    {
        $prefix = 'CMP-'.now()->format('Ymd');
        $sequence = Complaint::withTrashed()
            ->where('complaint_number', 'like', "{$prefix}-%")
            ->count() + 1;

        do {
            $complaintNumber = $prefix.'-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
            $sequence++;
        } while (Complaint::withTrashed()->where('complaint_number', $complaintNumber)->exists());

        return $complaintNumber;
    }
}
