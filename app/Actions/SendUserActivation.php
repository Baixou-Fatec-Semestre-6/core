<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserStatus;
use App\Enums\VerificationType;
use App\Mail\ActivationMail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class SendUserActivation
{
    public function handle(User $user): void
    {
        if ($user->status !== UserStatus::PENDING || $user->email_verified_at !== null) {
            throw ValidationException::withMessages([
                'activation' => 'Somente contas pendentes podem receber um e-mail de ativação.',
            ]);
        }

        $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($user, $code): void {
            $user->verificationCodes()
                ->where('type', VerificationType::ACTIVATION)
                ->delete();

            $user->verificationCodes()->create([
                'code_hash' => $code,
                'type' => VerificationType::ACTIVATION,
                'expires_at' => now()->addMinutes(10),
            ]);
        });

        Mail::to($user->email)->queue((new ActivationMail($code))->afterCommit());
    }
}
