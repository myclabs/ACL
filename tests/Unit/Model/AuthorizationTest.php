<?php

namespace Tests\MyCLabs\ACL\Unit\Model;

use MyCLabs\ACL\Model\Authorization;
use MyCLabs\ACL\Model\ClassResource;
use MyCLabs\ACL\Model\Identity;
use MyCLabs\ACL\Model\ResourceId;
use MyCLabs\ACL\Model\ResourceInterface;
use MyCLabs\ACL\Model\RoleEntry;
use Tests\MyCLabs\ACL\Integration\Model\Actions;

/**
 * @covers \MyCLabs\ACL\Model\Authorization
 */
class AuthorizationTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateWithClassResource()
    {
        $user = $this->getMockForAbstractClass(Identity::class);
        $role = $this->getMock(RoleEntry::class, [], [], '', false);
        $role->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue($user));

        $resource = new ClassResource(get_class());

        $authorization = Authorization::create($role, Actions::all(), $resource);

        $this->assertInstanceOf(Authorization::class, $authorization);
        $this->assertSame($role, $authorization->getRoleEntry());
        $this->assertSame($user, $authorization->getIdentity());
        $this->assertEquals(Actions::all(), $authorization->getActions());
        $this->assertEquals(get_class(), $authorization->getResourceId()->getName());
        $this->assertNull($authorization->getResourceId()->getId());
        $this->assertNull($authorization->getParentAuthorization());
        $this->assertTrue($authorization->isCascadable());
        $this->assertTrue($authorization->isRoot());
    }

    public function testCreateWithEntityResource()
    {
        $user = $this->getMockForAbstractClass(Identity::class);
        $role = $this->getMock(RoleEntry::class, [], [], '', false);
        $role->expects($this->once())
            ->method('getIdentity')
            ->will($this->returnValue($user));

        $resource = $this->getMockForAbstractClass(ResourceInterface::class);
        $resource->expects($this->once())
            ->method('getResourceId')
            ->will($this->returnValue(new ResourceId(get_class($resource), 1)));

        $authorization = Authorization::create($role, Actions::all(), $resource);

        $this->assertInstanceOf(Authorization::class, $authorization);
        $this->assertSame($role, $authorization->getRoleEntry());
        $this->assertSame($user, $authorization->getIdentity());
        $this->assertEquals(Actions::all(), $authorization->getActions());
        $this->assertEquals(get_class($resource), $authorization->getResourceId()->getName());
        $this->assertEquals(1, $authorization->getResourceId()->getId());
        $this->assertNull($authorization->getParentAuthorization());
        $this->assertTrue($authorization->isCascadable());
        $this->assertTrue($authorization->isRoot());
    }

    public function testCreateChildAuthorization()
    {
        $user = $this->getMockForAbstractClass(Identity::class);
        $role = $this->getMock(RoleEntry::class, [], [], '', false);
        $role->expects($this->any())
            ->method('getIdentity')
            ->will($this->returnValue($user));

        $resource = new ClassResource(get_class());
        $subResource = new ClassResource(get_class());

        $authorization = Authorization::create($role, Actions::all(), $resource);

        $childAuthorization = $authorization->createChildAuthorization($subResource);

        $this->assertInstanceOf(Authorization::class, $childAuthorization);
        $this->assertSame($authorization->getRoleEntry(), $childAuthorization->getRoleEntry());
        $this->assertSame($authorization->getIdentity(), $childAuthorization->getIdentity());
        $this->assertEquals($authorization->getActions(), $childAuthorization->getActions());
        $this->assertEquals(get_class(), $childAuthorization->getResourceId()->getName());
        $this->assertNull($childAuthorization->getResourceId()->getId());
        $this->assertSame($authorization, $childAuthorization->getParentAuthorization());
        $this->assertTrue($childAuthorization->isCascadable());
        $this->assertFalse($childAuthorization->isRoot());
    }
}
