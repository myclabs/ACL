<?php

namespace Tests\MyCLabs\ACL\Unit\Model;

use MyCLabs\ACL\Model\VirtualResource;

/**
 * @covers \MyCLabs\ACL\Model\VirtualResource
 */
class VirtualResourceTest extends \PHPUnit_Framework_TestCase
{
    public function testGetName()
    {
        $resource = new VirtualResource('foo');

        $this->assertEquals('foo', $resource->getName());
    }

    public function testGetId()
    {
        $resource = new VirtualResource('foo', 1);

        $this->assertEquals(1, $resource->getId());
    }

    public function testGetResourceId()
    {
        $resource = new VirtualResource('foo');
        $this->assertEquals('foo', $resource->getResourceId()->getName());
        $this->assertNull($resource->getResourceId()->getId());

        $resource = new VirtualResource('foo', 1);
        $this->assertEquals('foo', $resource->getResourceId()->getName());
        $this->assertEquals(1, $resource->getResourceId()->getId());
    }
}
