<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    public function test_role_checks_support_single_and_multiple_role_names(): void
    {
        $user = new User();
        $user->setRelation('roles', new Collection([
            (object) ['name' => 'admin'],
            (object) ['name' => 'Manager'],
        ]));

        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole(['admin']));
        $this->assertTrue($user->hasRole(['Partner', 'Manager']));
        $this->assertTrue($user->hasAnyRole(['Partner', 'admin']));
        $this->assertFalse($user->hasRole('Partner'));
        $this->assertFalse($user->hasRole(['Partner', 'Company_User']));
    }
}
