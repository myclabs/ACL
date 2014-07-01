<?php

namespace Tests\MyCLabs\ACL\Unit\Model;

use MyCLabs\ACL\ACL;
use MyCLabs\ACL\Model\Actions;
use MyCLabs\ACL\Model\ClassResource;
use MyCLabs\ACL\Model\ResourceInterface;
use MyCLabs\ACL\Model\Role;
use MyCLabs\ACL\Model\RoleEntry;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * @covers \MyCLabs\ACL\Model\Role
 */
class RoleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_should_return_its_name()
    {
        $role = Role::fromArray('foo', []);

        $this->assertSame('foo', $role->getName());
    }

    /**
     * @test
     */
    public function it_should_create_authorizations()
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
     * @test
     */
    public function it_should_validate_and_return_the_resource()
    {
        $resource = $this->given_a_resource();

        $role = Role::fromArray('foo', [
            'resource' => $resource,
        ]);

        $this->assertSame($resource, $role->validateAndReturnResourceForGrant());
    }

    /**
     * @test
     */
    public function it_should_error_when_overriding_the_configured_resource()
    {
        $resource = $this->given_a_resource();

        $role = Role::fromArray('foo', [
            'resource' => $resource,
        ]);

        $message = 'Cannot grant role foo on a resource of type ' . ClassResource::class . '.'
            . ' The role will be granted upon a resource that is set in the configuration and cannot be overridden';
        $this->setExpectedException('InvalidArgumentException', $message);

        $role->validateAndReturnResourceForGrant($resource);
    }

    /**
     * @test
     */
    public function it_should_error_when_the_resource_is_not_of_the_good_type()
    {
        $role = Role::fromArray('foo', [
            'resourceType' => 'FooBar',
        ]);

        $message = 'Cannot grant role foo on a resource of type ' . ClassResource::class . '.'
            . ' Per the configuration, foo can only be granted on resources of type FooBar';
        $this->setExpectedException('InvalidArgumentException', $message);

        $role->validateAndReturnResourceForGrant(new ClassResource('foo'));
    }

    /**
     * @test
     */
    public function it_should_error_when_no_resource()
    {
        $role = Role::fromArray('foo', [
        ]);

        $message = 'The role foo must be granted on a resource, no resource was given';
        $this->setExpectedException(\InvalidArgumentException::class, $message);

        $role->validateAndReturnResourceForGrant();
    }

    /**
     * @test
     */
    public function it_should_run_the_authorizations_callback()
    {
        $acl = $this->given_the_acl();
        $roleEntry = $this->given_a_role_entry();
        $resource = $this->given_a_resource();

        $called = false;

        $callback = function (ACL $aclParameter, RoleEntry $roleEntryParameter, ResourceInterface $resourceParameter)
                    use (&$called, $acl, $roleEntry, $resource) {
            $this->assertSame($acl, $aclParameter);
            $this->assertSame($roleEntry, $roleEntryParameter);
            $this->assertSame($resource, $resourceParameter);

            $called = true;
        };

        $role = Role::fromArray('foo', [
            'authorizations' => $callback,
        ]);

        $role->createAuthorizations($acl, $roleEntry, $resource);

        $this->assertTrue($called, 'The callback was not called');
    }

    /**
     * @return ACL|PHPUnit_Framework_MockObject_MockObject
     */
    private function given_the_acl()
    {
        return $this->getMock(ACL::class, [], [], '', false);
    }

    /**
     * @return RoleEntry
     */
    private function given_a_role_entry()
    {
        return $this->getMock(RoleEntry::class, [], [], '', false);
    }

    private function given_a_resource()
    {
        return new ClassResource('foo');
    }
}
