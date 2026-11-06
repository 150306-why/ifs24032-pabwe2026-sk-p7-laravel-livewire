<?php

namespace Tests\Feature\Profile;

use App\Features\Profile\Livewire\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        $this->user = User::factory()->create(['name' => 'Budi', 'username' => 'budi', 'email' => 'budi@x.test', 'password' => 'lama12345']);
    }

    private function page()
    {
        return Livewire::actingAs($this->user)->test(Profile::class);
    }

    public function test_form_is_prefilled(): void
    {
        $this->page()->assertSet('name', 'Budi')->assertSet('username', 'budi')->assertSet('email', 'budi@x.test');
    }

    public function test_update_profile(): void
    {
        $this->page()
            ->set('name', 'Budi Baru')
            ->set('username', 'budibaru')
            ->set('email', 'baru@x.test')
            ->call('updateProfile')
            ->assertHasNoErrors()
            ->assertSee('Profil berhasil diperbarui.');

        $this->assertSame('Budi Baru', $this->user->fresh()->name);
    }

    public function test_update_profile_allows_own_username_but_rejects_taken_one(): void
    {
        User::factory()->create(['username' => 'dipakai', 'email' => 'dipakai@x.test']);

        $this->page()->call('updateProfile')->assertHasNoErrors();

        $this->page()
            ->set('username', 'dipakai')
            ->set('email', 'dipakai@x.test')
            ->call('updateProfile')
            ->assertHasErrors(['username', 'email']);
    }

    public function test_change_password(): void
    {
        $this->page()
            ->set('current_password', 'lama12345')
            ->set('password', 'baru12345')
            ->set('password_confirmation', 'baru12345')
            ->call('updatePassword')
            ->assertHasNoErrors()
            ->assertSet('current_password', '')
            ->assertSee('Password berhasil diubah.');

        $this->assertTrue(Hash::check('baru12345', $this->user->fresh()->password));
    }

    public function test_change_password_failures(): void
    {
        $this->page()
            ->set('current_password', 'salah')
            ->set('password', 'baru12345')
            ->set('password_confirmation', 'baru12345')
            ->call('updatePassword')
            ->assertHasErrors(['current_password']);

        $this->page()
            ->set('current_password', 'lama12345')
            ->set('password', 'pendek')
            ->set('password_confirmation', 'beda')
            ->call('updatePassword')
            ->assertHasErrors(['password']);

        $this->assertTrue(Hash::check('lama12345', $this->user->fresh()->password));
    }

    public function test_photo_upload_and_removal(): void
    {
        $this->page()
            ->set('photo', UploadedFile::fake()->image('foto.png'))
            ->call('updatePhoto')
            ->assertHasNoErrors()
            ->assertSee('Foto profil berhasil diperbarui.');

        $path = $this->user->fresh()->profile_photo_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        $this->page()->assertSee('Hapus foto')->call('removePhoto')->assertSee('Foto profil dihapus.');
        $this->assertNull($this->user->fresh()->profile_photo_path);
    }

    public function test_photo_validation(): void
    {
        $this->page()->call('updatePhoto')->assertHasErrors(['photo' => 'required']);

        $this->page()
            ->set('photo', UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'))
            ->call('updatePhoto')
            ->assertHasErrors(['photo']);
    }
}
