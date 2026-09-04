<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ForgotPasswordRequest;
use App\Http\Requests\Admin\LoginRequest;
use App\Http\Requests\Admin\ResetPasswordRequest;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\View\View;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;

class AuthController extends Controller
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Login page
     */
    public function showLogin(Request $request): View
    {
        return view('auth.login');

    }

    /**
     * Handle login request
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');

        // Add a closure to handle complex/array-based column conditions
        $attempted = Auth::attempt(
            array_merge($credentials, [
                fn (Builder $query) => $query->whereIn('role', [
                    UserRole::ADMIN,
                    UserRole::SUPER_ADMIN,
                ]),
            ]),
            $request->filled('remember')
        );

        if (! $attempted) {
            return back()->withErrors([
                'email' => 'As credenciais fornecidas são inválidas ou você não tem permissão.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended('dashboard');
    }


    /**
     * Forgot password page
     */
    public function showForgotPassword(Request $request): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Log out the authenticated user
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendResetLink(ForgotPasswordRequest $request): RedirectResponse
    {
        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::ResetLinkSent
            ? back()->with(['status' => __($status)])
            : back()->withErrors(['email' => __($status)]);
    }

    public function showResetPassword(string $token): View
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    /**
     * Reset the user's password
     */
    public function resetPassword(ResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PasswordReset
            ? redirect()->route('admin.login')
                ->with('status', __($status))
                ->withInput($request->only('email'))
            : back()->withErrors(['email' => [__($status)]]);
    }
}