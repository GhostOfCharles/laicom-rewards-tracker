<?php

namespace App\Services;

use Carbon\CarbonImmutable;

class BusinessDate
{
    public static function today(): CarbonImmutable
    {
        return CarbonImmutable::now(config('lrts.business_timezone', 'Asia/Manila'))->startOfDay();
    }
}
