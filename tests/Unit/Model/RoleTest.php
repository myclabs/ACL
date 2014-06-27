<?php

namespace Tests\MyCLabs\ACL\Unit\Model;

use MyCLabs\ACL\ACL;
use MyCLabs\ACL\Model\Actions;
use MyCLabs\ACL\Model\ClassResource;
use MyCLabs\ACL\Model\Role;
use MyCLabs\ACL\Model\RoleEntry;
use PHPUnit_Framework_MockObject_MockObject;

class RoleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_should_create_authorizations_using_actions_as_array()
    {
        $acl = $this->given_the_acl();
        $roleEntry = $this->given_a_role_entry();
        $resource = $this->given_a_resource();

        $role = Role::fromArray('foo', [
            'actions' => [Actions::VIEW, Actions::EDIT],
        ]);

        $acl->expects($this->once())
            ->method('allow')
            ->with($roleEntry, new Actions([Actions::VIEW, Actions::EDIT]), $resource);

        $role->createAuthorizations($acl, $roleEntry, $resource);
    }

    /**
     * @test
     */
    public function it_should_create_authorizations_using_actions_as_object()
    {
        $acl = $this->given_the_acl();
        $roleEntry = $this->given_a_role_entry();
        $resource = $this->given_a_resource();

        $role = Role::fromArray('foo', [
            'actions' => new Actions([Actions::VIEW, Actions::EDIT]),
        ]);

        $acl->expects($this->once())
            ->method('allow')
            ->with($roleEntry, new Actions([Actions::VIEW, Actions::EDIT]), $resource);

        $role->createAuthorizations($acl, $roleEntry, $resource);
    }

    /**
     * @return ACL|PHPUnit_Framework_MockObject_MockObject
     */
    private function given_the_acl()
    {
        return $this->getMock('MyCLabs\ACL\ACL', [], [], '', false);
    }

    /**
     * @return RoleEntry
     */
    private function given_a_role_entry()
    {
        return $this->getMock('MyCLabs\ACL\Model\RoleEntry', [], [], '', false);
    }

    private function given_a_resource()
    {
        return new ClassResource('foo');
    }
}
