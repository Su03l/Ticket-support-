<?php

namespace App\Services;

use App\Models\Inquiry;

class InquiryNumberGenerator
{
    public function generate(): string
    {
        $prefix = 'INQ-'.now()->format('Ymd');
        $sequence = Inquiry::withTrashed()->where('inquiry_number', 'like', "{$prefix}-%")->count() + 1;

        do {
            $number = $prefix.'-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
            $sequence++;
        } while (Inquiry::withTrashed()->where('inquiry_number', $number)->exists());

        return $number;
    }
}
