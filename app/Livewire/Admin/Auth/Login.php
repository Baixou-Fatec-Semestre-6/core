<?php

namespace App\Livewire\Admin\Auth;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Entrar no Painel Admin')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    /**
     * @return array<string, list<string>>
     */
    protected function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ];
    }

    public function login(): void
    {
        $validated = $this->validate();

        $attempted = Auth::attempt(
            [
                'email' => $validated['email'],
                'password' => $validated['password'],
                fn (Builder $query) => $query->whereIn('role', [
                    UserRole::ADMIN,
                    UserRole::SUPER_ADMIN,
                ]),
            ],
            $validated['remember'],
        );

        if (! $attempted) {
            $this->reset('password');
            $this->addError('credentials', 'As credenciais fornecidas são inválidas ou você não tem permissão.');

            return;
        }

        session()->regenerate();

        $this->redirectIntended(route('admin.dashboard'));
    }
}
