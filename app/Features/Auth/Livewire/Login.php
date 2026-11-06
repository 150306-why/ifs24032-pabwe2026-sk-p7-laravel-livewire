<?php

namespace App\Features\Auth\Livewire;

use App\Features\Auth\Actions\LoginUserAction;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Masuk')]
class Login extends Component
{
    public string $login = '';

    public string $password = '';

    public bool $remember = false;

    protected function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function submit(LoginUserAction $action): void
    {
        $this->validate();

        if (! $action->execute($this->login, $this->password, $this->remember)) {
            $this->addError('login', 'Username/email atau password salah.');
            $this->reset('password');

            return;
        }

        session()->regenerate();

        $this->redirect(Auth::user()->homePath());
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }
}
