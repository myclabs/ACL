<?php

namespace Tests\MyCLabs\ACL\Unit\Model;

use MyCLabs\ACL\Model\Resource;

/**
 * @covers \MyCLabs\ACL\Model\Resource
 */
class ResourceTest extends \PHPUnit_Framework_TestCase
{
    public function testFromEntity()
    {
        $entity = $this->getMockForAbstractClass('MyCLabs\ACL\Model\EntityResourceInterface');
        $entity->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(1));

        $resource = Resource::fromEntity($entity);

        $this->assertTrue($resource instanceof Resource);
        $this->assertTrue($resource->isEntity());
        $this->assertFalse($resource->isEntityClass());
        $this->assertFalse($resource->isEntityField());
        $this->assertFalse($resource->isEntityClassField());

        $this->assertSame($entity, $resource->getEntity());
    }

    public function testFromEntityClass()
    {
        $resource = Resource::fromEntityClass('foo');

        $this->assertTrue($resource instanceof Resource);
        $this->assertFalse($resource->isEntity());
        $this->assertTrue($resource->isEntityClass());
        $this->assertFalse($resource->isEntityField());
        $this->assertFalse($resource->isEntityClassField());

        $this->assertSame('foo', $resource->getEntityClass());
    }

    public function testFromEntityField()
    {
        $entity = $this->getMockForAbstractClass('MyCLabs\ACL\Model\EntityResourceInterface');
        $entity->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(1));

        $resource = Resource::fromEntityField($entity, 'foo');

        $this->assertTrue($resource instanceof Resource);
        $this->assertFalse($resource->isEntity());
        $this->assertFalse($resource->isEntityClass());
        $this->assertTrue($resource->isEntityField());
        $this->assertFalse($resource->isEntityClassField());

        $this->assertSame($entity, $resource->getEntity());
        $this->assertSame('foo', $resource->getEntityField());
    }

    public function testFromEntityClassField()
    {
        $resource = Resource::fromEntityClassField('foo', 'bar');

        $this->assertTrue($resource instanceof Resource);
        $this->assertFalse($resource->isEntity());
        $this->assertFalse($resource->isEntityClass());
        $this->assertFalse($resource->isEntityField());
        $this->assertTrue($resource->isEntityClassField());

        $this->assertSame('foo', $resource->getEntityClass());
        $this->assertSame('bar', $resource->getEntityField());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage must be persisted (id not null) to be able to test the permissions
     */
    public function testFromEntityNotPersisted()
    {
        $entity = $this->getMockForAbstractClass('MyCLabs\ACL\Model\EntityResourceInterface');

        Resource::fromEntity($entity);
    }
}
