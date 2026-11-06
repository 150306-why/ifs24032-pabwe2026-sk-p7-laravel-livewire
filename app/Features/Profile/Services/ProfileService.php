<?php

namespace App\Features\Profile\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfileService
{
    public function updateProfile(User $user, array $data): User
    {
        $user->update(Arr::only($data, ['name', 'username', 'email']));

        return $user->refresh();
    }

    public function updatePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Password saat ini salah.',
            ]);
        }

        $user->update(['password' => $newPassword]);
    }

    public function updatePhoto(User $user, UploadedFile $file): User
    {
        $this->deletePhotoFile($user);

        $user->update(['profile_photo_path' => $file->store('profile-photos', 'public')]);

        return $user->refresh();
    }

    public function removePhoto(User $user): User
    {
        $this->deletePhotoFile($user);

        $user->update(['profile_photo_path' => null]);

        return $user->refresh();
    }

    private function deletePhotoFile(User $user): void
    {
        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }
    }
}
