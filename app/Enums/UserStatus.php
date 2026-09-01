<?php

namespace App\Enums;


/**
 * Status do usuário.
 */
enum UserStatus: string
{
    /** Registrado, mas ainda não verificou a conta. */
    case PENDING = 'pending';

    /** Ativo, com acesso padrão ao sistema. */
    case ACTIVE = 'active';

    /** Conta suspensa ou desativada. */
    case DISABLED = 'disabled';
}