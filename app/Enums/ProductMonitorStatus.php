<?php

namespace App\Enums;

enum ProductMonitorStatus: string
{
    case PENDING = 'pending';
    case SCRAPED = 'scraped';
    case FAILED = 'failed';
}
