<?php

namespace Tests\MyCLabs\ACL\Unit\Model;

use MyCLabs\ACL\Model\ClassResource;

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

    public function testParentResources()
    {
        $em = $this->getMock('Doctrine\ORM\EntityManager', [], [], '', false);
        $resource = new ClassResource('foo');

        $this->assertSame([], $resource->getParentResources($em));
    }

    public function testSubResources()
    {
        $repo = $this->getMock('MyCLabs\ACL\Repository\AuthorizationRepository', [], [], '', false);
        $repo->expects($this->once())
            ->method('findAll')
            ->will($this->returnValue(['foo', 'bar']));

        $em = $this->getMock('Doctrine\ORM\EntityManager', [], [], '', false);
        $em->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($repo));

        $resource = new ClassResource('foo');

        $this->assertSame(['foo', 'bar'], $resource->getSubResources($em));
    }
}
