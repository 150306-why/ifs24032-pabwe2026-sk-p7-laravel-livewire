<?php

namespace Tests\Unit\Models;

use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_and_home_path(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->member()->create();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isMember());
        $this->assertTrue($member->isMember());
        $this->assertSame(route('admin.users.index'), $admin->homePath());
        $this->assertSame(route('stocks.index'), $member->homePath());
    }

    public function test_password_is_hashed(): void
    {
        $user = User::factory()->create(['password' => 'rahasia123']);

        $this->assertNotSame('rahasia123', $user->password);
        $this->assertTrue(Hash::check('rahasia123', $user->password));
    }

    public function test_has_many_stocks(): void
    {
        $user = User::factory()->create();
        Stock::factory()->count(2)->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->stocks);
    }

    public function test_profile_photo_url_and_initials(): void
    {
        $user = User::factory()->create(['name' => 'budi santoso wijaya']);

        $this->assertNull($user->profilePhotoUrl());
        $this->assertSame('BS', $user->initials());

        $user->profile_photo_path = 'profile-photos/a.jpg';
        $this->assertStringContainsString('profile-photos/a.jpg', $user->profilePhotoUrl());
    }
}
