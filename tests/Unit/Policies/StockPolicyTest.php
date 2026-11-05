<?php

namespace Tests\Unit\Policies;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Policies\StockPolicy;
use App\Models\User;
use Tests\TestCase;

class StockPolicyTest extends TestCase
{
    public function test_owner_member_is_allowed(): void
    {
        $user = new User(['role' => 'member']);
        $user->id = 1;
        $stock = new Stock(['user_id' => 1]);
        $policy = new StockPolicy;

        $this->assertTrue($policy->view($user, $stock));
        $this->assertTrue($policy->update($user, $stock));
        $this->assertTrue($policy->delete($user, $stock));
    }

    public function test_non_owner_and_admin_are_denied(): void
    {
        $member = new User(['role' => 'member']);
        $member->id = 2;
        $admin = new User(['role' => 'admin']);
        $admin->id = 1;
        $stock = new Stock(['user_id' => 1]);
        $policy = new StockPolicy;

        $this->assertFalse($policy->view($member, $stock));
        $this->assertFalse($policy->update($admin, $stock));
        $this->assertFalse($policy->delete($admin, $stock));
    }
}
