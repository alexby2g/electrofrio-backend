<?php

namespace App\Support;

class PhoneNumber
{
    public static function normalize(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        if (!$digits) {
            return null;
        }

        if (strlen($digits) === 8) {
            $digits = '591'.$digits;
        }

        if (str_starts_with($digits, '00591')) {
            $digits = substr($digits, 2);
        }

        return '+'.$digits;
    }
}
