<?php

namespace Tests\Feature;

use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_by_role(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->actingAs(User::factory()->admin()->create())->get('/')->assertRedirect(route('admin.users.index'));
        $this->actingAs(User::factory()->create())->get('/')->assertRedirect(route('stocks.index'));
    }

    public function test_guest_pages_render_with_required_login_ids(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('STOCK THINGS')
            ->assertSee('id="login-username"', false)
            ->assertSee('id="login-password"', false)
            ->assertSee('id="login-submit"', false);

        $this->get('/register')->assertOk();
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $this->actingAs(User::factory()->create())->get('/login')->assertRedirect(route('stocks.index'));
    }

    public function test_guest_is_redirected_to_login_for_protected_pages(): void
    {
        $stock = Stock::factory()->create();

        $this->get('/stocks')->assertRedirect(route('login'));
        $this->get("/stocks/{$stock->id}")->assertRedirect(route('login'));
        $this->get('/profile')->assertRedirect(route('login'));
        $this->get('/admin/users')->assertRedirect(route('login'));
    }

    public function test_member_pages(): void
    {
        $member = User::factory()->create();
        $stock = Stock::factory()->create(['user_id' => $member->id, 'name' => 'Pensil Saya']);

        $this->actingAs($member)->get('/stocks')->assertOk()->assertSee('Pensil Saya')->assertSee('Inventaris');
        $this->actingAs($member)->get("/stocks/{$stock->id}")->assertOk()->assertSee('Pensil Saya');
        $this->actingAs($member)->get('/profile')->assertOk();
    }

    public function test_member_cannot_open_other_members_stock_or_admin_pages(): void
    {
        $member = User::factory()->create();
        $foreign = Stock::factory()->create();

        $this->actingAs($member)->get("/stocks/{$foreign->id}")->assertForbidden();
        $this->actingAs($member)->get('/admin/users')->assertForbidden();
        $this->actingAs($member)->get("/admin/users/{$foreign->user_id}/stocks")->assertForbidden();
    }

    public function test_admin_can_read_user_stocks_but_not_open_stock_detail_or_member_pages(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();
        $stock = Stock::factory()->create(['user_id' => $member->id, 'name' => 'Stok Member']);

        $this->actingAs($admin)->get('/admin/users')->assertOk()->assertSee($member->name);
        $this->actingAs($admin)->get("/admin/users/{$member->id}/stocks")->assertOk()->assertSee('Stok Member');
        $this->actingAs($admin)->get("/stocks/{$stock->id}")->assertForbidden();
        $this->actingAs($admin)->get('/stocks')->assertForbidden();
    }

    public function test_logout(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_logout_requires_post(): void
    {
        $this->actingAs(User::factory()->create())->get('/logout')->assertStatus(405);
    }
}
