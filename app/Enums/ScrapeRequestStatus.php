<?php

namespace App\Enums;

enum ScrapeRequestStatus: string
{
    case DISPATCHING = 'dispatching';
    case QUEUED = 'queued';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
    case IGNORED = 'ignored';
    case DISPATCH_FAILED = 'dispatch_failed';
}
