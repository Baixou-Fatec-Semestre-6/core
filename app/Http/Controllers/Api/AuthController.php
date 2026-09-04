<?php

namespace App\Http\Controllers\Api;

use App\Services\Api\AuthService;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\Api\RegisterUserRequest;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\ActivateAccountRequest;
use App\Http\Requests\Api\ForgotPasswordRequest;
use App\Http\Requests\Api\VerifyResetCodeRequest;
use App\Http\Requests\Api\ResetPasswordRequest;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use \App\Http\Controllers\Controller;

class AuthController extends Controller
{

    /**
     * Create a new class instance.
     */
    public function __construct(private AuthService $authService)
    {
        //
    }

    /**
     * Register a new user account.
     */
    public function register(RegisterUserRequest $request): UserResource
    {
        $user = $this->authService->register($request->validated());

        return (new UserResource($user))
            ->additional(['message' => 'Conta criada com sucesso. Verifique seu e-mail.']);
    }

    /**
     * Authenticate the user and issue an access token.
     */
    public function login(LoginRequest $request): UserResource
    {
        $result = $this->authService->login($request->validated());

        return (new UserResource($result['user']))
            ->additional(['token' => $result['token']]);
    }

    /**
     * Log out the authenticated user and revoke the current access token.
     */
    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return response()->json([
            'message' => 'Logout efetuado com sucesso.',
        ], 200);
    }

    /**
     * Retrieve the currently authenticated user.
     */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * Activate a user account using the verification code sent by email.
     */
    public function activate(ActivateAccountRequest $request): JsonResponse
    {
        $this->authService->activate($request->validated());

        return response()->json([
            'message' => 'Conta ativada com sucesso.',
        ], 200);
    }

    /**
     * Send a password reset code to the provided email address.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->forgotPassword($request->validated());

        return response()->json([
            'mensagem' => 'Foi enviado um código de redefinição para o e-mail informado.',
        ], 200);
    }

    /**
     * Verify a password reset code and issue a reset token.
     */
    public function verifyResetCode(VerifyResetCodeRequest $request): JsonResponse
    {
        $result = $this->authService->verifyResetCode($request->validated());

        return response()->json([
            'mensagem' => 'Código de redefinição verificado com sucesso.',
            'reset_token' => $result['reset_token'],
        ], 200);
    }

    /**
     * Reset the user's password using a valid reset token.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->authService->resetPassword($request->validated());

        return response()->json([
            'message' => 'Senha redefinida com sucesso.',
        ], 200);
    }
}
