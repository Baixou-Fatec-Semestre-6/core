<?php

namespace App\Services\Api;

use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Enums\VerificationType;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use App\Enums\UserStatus;
use App\Mail\ActivationMail;
use App\Mail\ResetPasswordMail;


class AuthService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }


    /**
     * Register a new user.
     *
     * @param array $data The user data.
     * @param string $data['email'] The user email.
     * @param string|null $data['name'] The user name. If not provided, the name will be extracted from the email.
     * @param string $data['password'] The user password.
     * @return User The newly created user.
     */
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = new User();
            $user->fill($data);

            if (empty($user->name)) {
                $user->name = Str::before($data['email'], '@');
            }

            $user->saveOrFail();

            // Criar código de verificação
            $verification = new VerificationCode();
            $verification->user_id = $user->id;
            $verification->code_hash = fake()->numerify('####');
            $verification->type = VerificationType::ACTIVATION;
            $verification->expires_at = Carbon::parse('+10 minutes');
            $verification->saveOrFail();

            // Enviar email de verificação
            Mail::to($user->email)->queue(new ActivationMail($verification->code_hash));

            return $user;
        });
    }


    /**
     * Logs in a user.
     *
     * @param array $credentials The user's credentials.
     * @param string $credentials['email'] The user's email.
     * @param string $credentials['password'] The user's password.
     * @return array(token: string, user: User) The logged in user.
     * @throws ValidationException If the credentials are invalid.
     */
    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Auth::validate($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas são inválidas.'],
            ]);
        }

        if ($user->status === UserStatus::PENDING) {
            throw ValidationException::withMessages([
                'email' => ['Sua conta ainda não foi ativada. Verifique seu e-mail.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }


    /**
     * Logout the authenticated user.
     * 
     * @return void
     */
    public function logout(): void
    {
        Auth::user()->tokens()->delete();
    }

    /**
     * Activate the user's account.
     * 
     * @param array $data The activation data.
     * @param string $data['email'] The user's email.
     * @param string $data['code'] The activation code.
     * @return void
     * @throws ValidationException If the activation code is invalid.
     */
    public function activate(array $data): void
    {
        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas são inválidas.'],
            ]);
        }

        $verification = $user->verificationCodes()
            ->latest()
            ->where('type', VerificationType::ACTIVATION)
            ->first();

        if (! $verification || ! $verification->isValid()) {
            throw ValidationException::withMessages([
                'code' => ['O código de ativação é inválido ou expirou.'],
            ]);
        }

        if ($verification->code_hash != $data['code']) {
            $verification->increment('attempts');

            throw ValidationException::withMessages([
                'code' => ['O código de ativação é inválido ou expirou.'],
            ]);
        }

        DB::transaction(function () use ($user, $verification) {
            $user->email_verified_at = Carbon::now();
            $user->status = UserStatus::ACTIVE;
            $user->save();

            $verification->used_at = Carbon::now();
            $verification->save();
        });
    }

    /**
     * Send a password reset code to the user's email.
     *
     * @param array $data The user data.
     * @param string $data['email'] The user email.
     * @return void
     * @throws ValidationException If the user is not found.
     */
    public function forgotPassword(array $data): void
    {
        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            // Não revela se o usuário existe, dificultando ataques de brute force
            return;
        }

        DB::transaction(function () use ($user) {
            // Deletar códigos de redefinição de senha existentes para este usuário
            $user->verificationCodes()
                ->where('type', VerificationType::PASSWORD_RESET)
                ->delete();

            // Criar novo código de redefinição de senha
            $verification = new VerificationCode();
            $verification->user_id = $user->id;
            $verification->code_hash = fake()->numerify('####');
            $verification->type = VerificationType::PASSWORD_RESET;
            $verification->expires_at = Carbon::parse('+10 minutes');
            $verification->token = Str::random(60);
            $verification->saveOrFail();

            // Enviar email com código de redefinição
            Mail::to($user->email)->queue(new ResetPasswordMail($verification->code_hash));
        });
    }

    /**
     * Verify the password reset code provided by the user.
     *
     * @param array $data The verification data.
     * @param string $data['email'] The user email.
     * @param string $data['code'] The reset code.
     * @return array The verification result with reset token.
     * @throws ValidationException If the code is invalid.
     */
    public function verifyResetCode(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'code' => ['O código de redefinição é inválido ou expirou.'],
            ]);
        }

        $verification = $user->verificationCodes()
            ->latest()
            ->where('type', VerificationType::PASSWORD_RESET)
            ->first();

        if (! $verification || ! $verification->isValid()) {
            throw ValidationException::withMessages([
                'code' => ['O código de redefinição é inválido ou expirou.'],
            ]);
        }

        if ($verification->code_hash != $data['code']) {
            $verification->increment('attempts');

            throw ValidationException::withMessages([
                'code' => ['O código de redefinição é inválido ou expirou.'],
            ]);
        }

        return [
            'reset_token' => $verification->token,
        ];
    }

    /**
     * Reset the user's password using a verified reset token.
     *
     * @param array $data The reset data.
     * @param string $data['reset_token'] The reset token.
     * @param string $data['password'] The new password.
     * @param string $data['password_confirmation'] The password confirmation.
     * @return void
     * @throws ValidationException If the reset token is invalid.
     */
    public function resetPassword(array $data): void
    {
        $verification = VerificationCode::latest()
            ->where('type', VerificationType::PASSWORD_RESET)
            ->where('token', $data['reset_token'])
            ->first();

        if (! $verification || ! $verification->isValid()) {
            throw ValidationException::withMessages([
                'reset_token' => ['O código de redefinição é inválido ou expirou.'],
            ]);
        }

        DB::transaction(function () use ($verification, $data) {
            $user = $verification->user;

            // Atualizar senha do usuário
            $user->password = $data['password'];
            if ($user->status === UserStatus::PENDING) {
                $user->email_verified_at = Carbon::now();
                $user->status = UserStatus::ACTIVE;
            }
            $user->save();

            // Marcar verificação como usada
            $verification->used_at = Carbon::now();
            $verification->save();

            // Deletar todos os códigos de redefinição de senha deste usuário
            $user->verificationCodes()
                ->where('type', VerificationType::PASSWORD_RESET)
                ->delete();
        });
    }
}
