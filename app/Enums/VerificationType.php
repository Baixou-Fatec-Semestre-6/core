<?php

namespace App\Enums;

enum VerificationType: string
{
    case ACTIVATION = 'activation';
    case PASSWORD_RESET = 'password_reset';
}