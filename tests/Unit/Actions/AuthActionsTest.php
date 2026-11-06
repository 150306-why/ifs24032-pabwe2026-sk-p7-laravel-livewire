<?php

namespace Tests\Unit\Actions;

use App\Features\Auth\Actions\LoginUserAction;
use App\Features\Auth\Actions\RegisterUserAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_username_and_email(): void
    {
        User::factory()->create(['username' => 'budi', 'email' => 'budi@x.test', 'password' => 'rahasia123']);
        $action = new LoginUserAction;

        $this->assertTrue($action->execute('budi', 'rahasia123'));
        Auth::logout();
        $this->assertTrue($action->execute('budi@x.test', 'rahasia123', true));
        Auth::logout();
        $this->assertFalse($action->execute('budi', 'salah'));
        $this->assertFalse($action->execute('tidak-ada', 'rahasia123'));
    }

    public function test_register_creates_member_and_logs_in(): void
    {
        $user = (new RegisterUserAction)->execute([
            'name' => 'Siti', 'username' => 'siti', 'email' => 'siti@x.test', 'password' => 'rahasia123',
        ]);

        $this->assertTrue($user->isMember());
        $this->assertTrue(Hash::check('rahasia123', $user->password));
        $this->assertSame($user->id, Auth::id());
    }
}
