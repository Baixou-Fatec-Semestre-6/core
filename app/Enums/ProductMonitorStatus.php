<?php

namespace App\Enums;

enum ProductMonitorStatus: string
{
    case PENDING = 'pending';
    case SUCCESS = 'success';
    case FAILED = 'failed';
}
