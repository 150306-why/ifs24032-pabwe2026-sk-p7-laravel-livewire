<?php

namespace Tests\Unit\Services;

use App\Features\Profile\Services\ProfileService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProfileService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->service = new ProfileService;
    }

    public function test_update_profile_only_changes_allowed_fields(): void
    {
        $user = User::factory()->create();

        $updated = $this->service->updateProfile($user, [
            'name' => 'Nama Baru', 'username' => 'baru', 'email' => 'baru@x.test', 'role' => 'admin',
        ]);

        $this->assertSame('Nama Baru', $updated->name);
        $this->assertSame('baru', $updated->username);
        $this->assertSame('baru@x.test', $updated->email);
        $this->assertSame('member', $updated->role, 'role tidak boleh bisa diubah lewat profil');
    }

    public function test_update_password(): void
    {
        $user = User::factory()->create(['password' => 'lama12345']);

        $this->service->updatePassword($user, 'lama12345', 'baru12345');

        $this->assertTrue(Hash::check('baru12345', $user->fresh()->password));
    }

    public function test_update_password_with_wrong_current_password_fails(): void
    {
        $user = User::factory()->create(['password' => 'lama12345']);

        $this->expectException(ValidationException::class);

        $this->service->updatePassword($user, 'salah', 'baru12345');
    }

    public function test_photo_upload_replace_and_remove(): void
    {
        $user = User::factory()->create();

        $user = $this->service->updatePhoto($user, UploadedFile::fake()->image('a.jpg'));
        $first = $user->profile_photo_path;
        Storage::disk('public')->assertExists($first);

        $user = $this->service->updatePhoto($user, UploadedFile::fake()->image('b.jpg'));
        Storage::disk('public')->assertMissing($first);
        $second = $user->profile_photo_path;
        Storage::disk('public')->assertExists($second);

        $user = $this->service->removePhoto($user);
        Storage::disk('public')->assertMissing($second);
        $this->assertNull($user->profile_photo_path);

        $this->assertNull($this->service->removePhoto($user)->profile_photo_path);
    }
}
