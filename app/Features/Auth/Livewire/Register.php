<?php

namespace App\Features\Auth\Livewire;

use App\Features\Auth\Actions\RegisterUserAction;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Daftar')]
class Register extends Component
{
    public string $name = '';

    public string $username = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'username' => 'required|string|alpha_dash|min:3|max:50|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:5|confirmed',
        ];
    }

    public function submit(RegisterUserAction $action)
    {
        $data = $this->validate();
        $action->execute($data);

        return $this->redirect(route('home'), navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}
