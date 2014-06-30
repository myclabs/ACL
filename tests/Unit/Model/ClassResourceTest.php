<?php

namespace Tests\MyCLabs\ACL\Unit\Model;

use Doctrine\ORM\EntityManager;
use MyCLabs\ACL\Model\ClassResource;
use MyCLabs\ACL\Repository\AuthorizationRepository;

/**
 * @covers \MyCLabs\ACL\Model\ClassResource
 */
class ClassResourceTest extends \PHPUnit_Framework_TestCase
{
    public function testGetClass()
    {
        $resource = new ClassResource('foo');

        $this->assertEquals('foo', $resource->getClass());
    }

    public function testGetResourceId()
    {
        $resource = new ClassResource('foo');

        $this->assertEquals('foo', $resource->getResourceId()->getName());
        $this->assertNull($resource->getResourceId()->getId());
    }

    public function testParentResources()
    {
        $em = $this->getMock(EntityManager::class, [], [], '', false);
        $resource = new ClassResource('foo');

        $this->assertSame([], $resource->getParentResources($em));
    }

    public function testSubResources()
    {
        $repo = $this->getMock(AuthorizationRepository::class, [], [], '', false);
        $repo->expects($this->once())
            ->method('findAll')
            ->will($this->returnValue(['foo', 'bar']));

        $em = $this->getMock(EntityManager::class, [], [], '', false);
        $em->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($repo));

        $resource = new ClassResource('foo');

        $this->assertSame(['foo', 'bar'], $resource->getSubResources($em));
    }
}
