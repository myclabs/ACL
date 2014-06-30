<?php

namespace Tests\MyCLabs\ACL\Unit\Model;

use Doctrine\ORM\EntityManager;
use MyCLabs\ACL\Model\CascadingResource;
use MyCLabs\ACL\Model\ResourceInterface;
use MyCLabs\ACL\ResourceGraph\CascadingResourceGraphTraverser;
use MyCLabs\ACL\ResourceGraph\ResourceGraphTraverser;

/**
 * @covers \MyCLabs\ACL\ResourceGraph\CascadingResourceGraphTraverser
 */
class CascadingResourceGraphTraverserTest extends \PHPUnit_Framework_TestCase
{
    public function testGetParentResources()
    {
        $em = $this->getMock(EntityManager::class, [], [], '', false);

        $parentTraverser = $this->getMockForAbstractClass(ResourceGraphTraverser::class);
        $traverser = new CascadingResourceGraphTraverser($em, $parentTraverser);

        $cascadingResource = $this->getMockForAbstractClass(CascadingResource::class);

        // Check that getParentResources() is called on the CascadingResource
        $cascadingResource->expects($this->once())
            ->method('getParentResources')
            ->will($this->returnValue([]));

        $this->assertEmpty($traverser->getAllParentResources($cascadingResource));
    }

    /**
     * Test that getAllParentResources() also resolves parent resources recursively
     * through the parent traverser
     */
    public function testGetParentResourcesRecursive()
    {
        $em = $this->getMock(EntityManager::class, [], [], '', false);

        $parentTraverser = $this->getMockForAbstractClass(ResourceGraphTraverser::class);
        $traverser = new CascadingResourceGraphTraverser($em, $parentTraverser);

        $cascadingResource = $this->getMockForAbstractClass(CascadingResource::class);
        $parentResource = $this->getMockForAbstractClass(ResourceInterface::class);

        $cascadingResource->expects($this->once())
            ->method('getParentResources')
            ->will($this->returnValue([ $parentResource ]));

        // Check that getAllParentResources() on the $parentTraverser is called
        // => recursively getting parent resources
        $parentTraverser->expects($this->once())
            ->method('getAllParentResources')
            ->with($parentResource)
            ->will($this->returnValue([]));

        $parentResources = $traverser->getAllParentResources($cascadingResource);

        $this->assertNotEmpty($parentResources);
        $this->assertSame($parentResource, $parentResources[0]);
    }

    public function testGetSubResources()
    {
        $em = $this->getMock(EntityManager::class, [], [], '', false);

        $parentTraverser = $this->getMockForAbstractClass(ResourceGraphTraverser::class);
        $traverser = new CascadingResourceGraphTraverser($em, $parentTraverser);

        $cascadingResource = $this->getMockForAbstractClass(CascadingResource::class);

        // Check that getParentResources() is called on the CascadingResource
        $cascadingResource->expects($this->once())
            ->method('getSubResources')
            ->will($this->returnValue([]));

        $this->assertEmpty($traverser->getAllSubResources($cascadingResource));
    }

    /**
     * Test that getAllSubResources() also resolves sub-resources recursively
     * through the parent traverser
     */
    public function testGetSubResourcesRecursive()
    {
        $em = $this->getMock(EntityManager::class, [], [], '', false);

        $parentTraverser = $this->getMockForAbstractClass(ResourceGraphTraverser::class);
        $traverser = new CascadingResourceGraphTraverser($em, $parentTraverser);

        $cascadingResource = $this->getMockForAbstractClass(CascadingResource::class);
        $subResource = $this->getMockForAbstractClass(ResourceInterface::class);

        $cascadingResource->expects($this->once())
            ->method('getSubResources')
            ->will($this->returnValue([ $subResource ]));

        // Check that getAllParentResources() on the $parentTraverser is called
        // => recursively getting parent resources
        $parentTraverser->expects($this->once())
            ->method('getAllSubResources')
            ->with($subResource)
            ->will($this->returnValue([]));

        $subResources = $traverser->getAllSubResources($cascadingResource);

        $this->assertNotEmpty($subResources);
        $this->assertSame($subResource, $subResources[0]);
    }
}
