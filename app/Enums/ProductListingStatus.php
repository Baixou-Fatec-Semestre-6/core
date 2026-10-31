<?php

namespace App\Enums;

enum ProductListingStatus: string
{
    case PENDING = 'pending';
    case SCRAPED = 'scraped';
    case FAILED = 'failed';
}
