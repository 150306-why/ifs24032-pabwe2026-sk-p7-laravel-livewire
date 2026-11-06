<?php

namespace Tests\Unit\Policies;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Policies\StockPolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_member_can_do_everything(): void
    {
        $member = User::factory()->create();
        $stock = Stock::factory()->create(['user_id' => $member->id]);
        $policy = new StockPolicy;

        $this->assertTrue($policy->viewAny($member));
        $this->assertTrue($policy->create($member));
        $this->assertTrue($policy->view($member, $stock));
        $this->assertTrue($policy->update($member, $stock));
        $this->assertTrue($policy->delete($member, $stock));
    }

    public function test_other_member_is_denied(): void
    {
        $other = User::factory()->create();
        $stock = Stock::factory()->create();
        $policy = new StockPolicy;

        $this->assertFalse($policy->view($other, $stock));
        $this->assertFalse($policy->update($other, $stock));
        $this->assertFalse($policy->delete($other, $stock));
    }

    public function test_admin_is_read_only_and_cannot_manage_stock(): void
    {
        $admin = User::factory()->admin()->create();
        $stock = Stock::factory()->create();
        $policy = new StockPolicy;

        $this->assertFalse($policy->viewAny($admin));
        $this->assertFalse($policy->create($admin));
        $this->assertFalse($policy->view($admin, $stock));
        $this->assertFalse($policy->update($admin, $stock));
        $this->assertFalse($policy->delete($admin, $stock));
    }
}
