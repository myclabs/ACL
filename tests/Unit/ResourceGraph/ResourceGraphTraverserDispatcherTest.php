<?php

namespace Tests\MyCLabs\ACL\Unit\Model;

use MyCLabs\ACL\Model\ResourceId;
use MyCLabs\ACL\Model\ResourceInterface;
use MyCLabs\ACL\ResourceGraph\ResourceGraphTraverser;
use MyCLabs\ACL\ResourceGraph\ResourceGraphTraverserDispatcher;

/**
 * @covers \MyCLabs\ACL\ResourceGraph\ResourceGraphTraverserDispatcher
 */
class ResourceGraphTraverserDispatcherTest extends \PHPUnit_Framework_TestCase
{
    public function testGetParentResources()
    {
        $traverser = new ResourceGraphTraverserDispatcher();

        $resource = $this->getMockForAbstractClass(ResourceInterface::class);
        $resource->expects($this->any())
            ->method('getResourceId')
            ->will($this->returnValue(new ResourceId(get_class($resource))));

        $subTraverser = $this->getMockForAbstractClass(ResourceGraphTraverser::class);
        $subTraverser->expects($this->once())
            ->method('getAllParentResources')
            ->with($resource)
            ->will($this->returnValue([ 'foo' ]));

        $traverser->setResourceGraphTraverser(get_class($resource), $subTraverser);

        $this->assertEquals([ 'foo' ], $traverser->getAllParentResources($resource));
    }

    public function testGetSubResources()
    {
        $traverser = new ResourceGraphTraverserDispatcher();

        $resource = $this->getMockForAbstractClass(ResourceInterface::class);
        $resource->expects($this->any())
            ->method('getResourceId')
            ->will($this->returnValue(new ResourceId(get_class($resource))));

        $subTraverser = $this->getMockForAbstractClass(ResourceGraphTraverser::class);
        $subTraverser->expects($this->once())
            ->method('getAllSubResources')
            ->with($resource)
            ->will($this->returnValue([ 'foo' ]));

        $traverser->setResourceGraphTraverser(get_class($resource), $subTraverser);

        $this->assertEquals([ 'foo' ], $traverser->getAllSubResources($resource));
    }

    public function testSetTraverserWithExactClass()
    {
        $traverser = new ResourceGraphTraverserDispatcher();
        $resource = $this->getMockForAbstractClass(ResourceInterface::class);
        $resource->expects($this->any())
            ->method('getResourceId')
            ->will($this->returnValue(new ResourceId(get_class($resource))));
        $subTraverser = $this->getMockForAbstractClass(ResourceGraphTraverser::class);

        // Set the traverser by passing the class of the resource
        $traverser->setResourceGraphTraverser(get_class($resource), $subTraverser);

        // Check that the $subTraverser is indeed called
        $subTraverser->expects($this->once())
            ->method('getAllParentResources')
            ->with($resource);

        $traverser->getAllParentResources($resource);
    }

    public function testSetTraverserWithInterface()
    {
        $traverser = new ResourceGraphTraverserDispatcher();
        $resource = $this->getMockForAbstractClass(ResourceInterface::class);
        $resource->expects($this->any())
            ->method('getResourceId')
            ->will($this->returnValue(new ResourceId(get_class($resource))));
        $subTraverser = $this->getMockForAbstractClass(ResourceGraphTraverser::class);

        // Set the traverser by passing the interface (and not the concrete class)
        $traverser->setResourceGraphTraverser(ResourceInterface::class, $subTraverser);

        // Check that the $subTraverser is indeed called
        $subTraverser->expects($this->once())
            ->method('getAllParentResources')
            ->with($resource);

        $traverser->getAllParentResources($resource);
    }
}
