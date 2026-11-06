<?php

namespace Tests\Feature\Auth;

use App\Features\Auth\Livewire\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_logs_in_with_username(): void
    {
        User::factory()->create(['username' => 'member', 'password' => 'member']);

        Livewire::test(Login::class)
            ->set('login', 'member')
            ->set('password', 'member')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('stocks.index'));

        $this->assertAuthenticated();
    }

    public function test_admin_logs_in_with_email_and_is_redirected_to_users(): void
    {
        User::factory()->admin()->create(['email' => 'admin@x.test', 'password' => 'admin']);

        Livewire::test(Login::class)
            ->set('login', 'admin@x.test')
            ->set('password', 'admin')
            ->call('submit')
            ->assertRedirect(route('admin.users.index'));
    }

    public function test_wrong_credentials_show_error_and_clear_password(): void
    {
        User::factory()->create(['username' => 'member', 'password' => 'member']);

        Livewire::test(Login::class)
            ->set('login', 'member')
            ->set('password', 'salah')
            ->call('submit')
            ->assertHasErrors(['login'])
            ->assertSet('password', '');

        $this->assertGuest();
    }

    public function test_fields_are_required(): void
    {
        Livewire::test(Login::class)
            ->call('submit')
            ->assertHasErrors(['login' => 'required', 'password' => 'required']);
    }
}
