<?php

namespace Tests\MyCLabs\ACL\Unit\Model;

use MyCLabs\ACL\Model\Authorization;
use MyCLabs\ACL\Model\ClassResource;
use Tests\MyCLabs\ACL\Integration\Model\Actions;

/**
 * @covers \MyCLabs\ACL\Model\Authorization
 */
class AuthorizationTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateWithClassResource()
    {
        $user = $this->getMockForAbstractClass('MyCLabs\ACL\Model\SecurityIdentityInterface');
        $role = $this->getMock('MyCLabs\ACL\Model\RoleEntry', [], [], '', false);
        $role->expects($this->once())
            ->method('getSecurityIdentity')
            ->will($this->returnValue($user));

        $resource = new ClassResource(get_class());

        $authorization = Authorization::create($role, Actions::all(), $resource);

        $this->assertInstanceOf('MyCLabs\ACL\Model\Authorization', $authorization);
        $this->assertSame($role, $authorization->getRole());
        $this->assertSame($user, $authorization->getSecurityIdentity());
        $this->assertEquals(Actions::all(), $authorization->getActions());
        $this->assertEquals(get_class(), $authorization->getEntityClass());
        $this->assertNull($authorization->getEntityId());
        $this->assertNull($authorization->getParentAuthorization());
        $this->assertTrue($authorization->isCascadable());
        $this->assertTrue($authorization->isRoot());
    }

    public function testCreateWithEntityResource()
    {
        $user = $this->getMockForAbstractClass('MyCLabs\ACL\Model\SecurityIdentityInterface');
        $role = $this->getMock('MyCLabs\ACL\Model\RoleEntry', [], [], '', false);
        $role->expects($this->once())
            ->method('getSecurityIdentity')
            ->will($this->returnValue($user));

        $resource = $this->getMockForAbstractClass('MyCLabs\ACL\Model\EntityResource');
        $resource->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(1));

        $authorization = Authorization::create($role, Actions::all(), $resource);

        $this->assertInstanceOf('MyCLabs\ACL\Model\Authorization', $authorization);
        $this->assertSame($role, $authorization->getRole());
        $this->assertSame($user, $authorization->getSecurityIdentity());
        $this->assertEquals(Actions::all(), $authorization->getActions());
        $this->assertEquals(get_class($resource), $authorization->getEntityClass());
        $this->assertEquals(1, $authorization->getEntityId());
        $this->assertNull($authorization->getParentAuthorization());
        $this->assertTrue($authorization->isCascadable());
        $this->assertTrue($authorization->isRoot());
    }

    public function testCreateChildAuthorization()
    {
        $user = $this->getMockForAbstractClass('MyCLabs\ACL\Model\SecurityIdentityInterface');
        $role = $this->getMock('MyCLabs\ACL\Model\RoleEntry', [], [], '', false);
        $role->expects($this->any())
            ->method('getSecurityIdentity')
            ->will($this->returnValue($user));

        $resource = new ClassResource(get_class());
        $subResource = new ClassResource(get_class());

        $authorization = Authorization::create($role, Actions::all(), $resource);

        $childAuthorization = $authorization->createChildAuthorization($subResource);

        $this->assertInstanceOf('MyCLabs\ACL\Model\Authorization', $childAuthorization);
        $this->assertSame($authorization->getRole(), $childAuthorization->getRole());
        $this->assertSame($authorization->getSecurityIdentity(), $childAuthorization->getSecurityIdentity());
        $this->assertEquals($authorization->getActions(), $childAuthorization->getActions());
        $this->assertEquals(get_class(), $childAuthorization->getEntityClass());
        $this->assertNull($childAuthorization->getEntityId());
        $this->assertSame($authorization, $childAuthorization->getParentAuthorization());
        $this->assertTrue($childAuthorization->isCascadable());
        $this->assertFalse($childAuthorization->isRoot());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testCreateAuthorizationWithInvalidResourceClass()
    {
        $role = $this->getMock('MyCLabs\ACL\Model\RoleEntry', [], [], '', false);
        $resource = $this->getMock('MyCLabs\ACL\Model\ResourceInterface');

        Authorization::create($role, Actions::all(), $resource);
    }
}
