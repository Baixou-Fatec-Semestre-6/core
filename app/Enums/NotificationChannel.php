<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case EMAIL = 'email';
    case IN_APP = 'in_app';
    case ALL = 'all';
}