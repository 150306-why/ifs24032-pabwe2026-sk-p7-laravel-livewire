<?php

namespace App\Features\Profile\Livewire;

use App\Features\Profile\Services\ProfileService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Profil')]
class Profile extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $username = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public $photo = null;

    public function mount(): void
    {
        $user = Auth::user();

        $this->name = $user->name;
        $this->username = $user->username;
        $this->email = $user->email;
    }

    public function updateProfile(ProfileService $service): void
    {
        $id = Auth::id();

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'alpha_dash', 'min:3', 'max:50', Rule::unique('users', 'username')->ignore($id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
        ]);

        $service->updateProfile(Auth::user(), $data);

        session()->flash('status', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(ProfileService $service): void
    {
        $this->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $service->updatePassword(Auth::user(), $this->current_password, $this->password);

        $this->reset('current_password', 'password', 'password_confirmation');

        session()->flash('status', 'Password berhasil diubah.');
    }

    public function updatePhoto(ProfileService $service): void
    {
        $this->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $service->updatePhoto(Auth::user(), $this->photo);

        $this->reset('photo');

        session()->flash('status', 'Foto profil berhasil diperbarui.');
    }

    public function removePhoto(ProfileService $service): void
    {
        $service->removePhoto(Auth::user());

        session()->flash('status', 'Foto profil dihapus.');
    }

    public function render(): View
    {
        return view('livewire.profile.profile', ['user' => Auth::user()->refresh()]);
    }
}
