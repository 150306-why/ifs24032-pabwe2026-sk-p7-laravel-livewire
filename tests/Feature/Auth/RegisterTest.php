<?php

namespace Tests\Feature\Auth;

use App\Features\Auth\Livewire\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_registers_a_member_and_redirects_to_stocks(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Siti Aminah')
            ->set('username', 'siti')
            ->set('email', 'siti@x.test')
            ->set('password', 'rahasia123')
            ->set('password_confirmation', 'rahasia123')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('stocks.index'));

        $user = User::where('username', 'siti')->first();
        $this->assertTrue($user->isMember());
        $this->assertAuthenticatedAs($user);
    }

    public function test_validation_failures(): void
    {
        User::factory()->create(['username' => 'siti', 'email' => 'siti@x.test']);

        Livewire::test(Register::class)
            ->set('name', '')
            ->set('username', 'siti')
            ->set('email', 'siti@x.test')
            ->set('password', 'pendek')
            ->set('password_confirmation', 'beda')
            ->call('submit')
            ->assertHasErrors(['name', 'username', 'email', 'password']);

        $this->assertGuest();
    }
}
