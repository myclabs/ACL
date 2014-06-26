<?php

namespace Tests\MyCLabs\ACL\Unit\Model;

use MyCLabs\ACL\CascadeStrategy\SimpleCascadeStrategy;

/**
 * @covers \MyCLabs\ACL\CascadeStrategy\SimpleCascadeStrategy
 */
class SimpleCascadeStrategyTest extends \PHPUnit_Framework_TestCase
{
    public function testSimpleCascade()
    {
        $em = $this->getMock('Doctrine\ORM\EntityManager', [], [], '', false);

        $strategy = new SimpleCascadeStrategy($em);

        $subResource = $this->getMockForAbstractClass('MyCLabs\ACL\Model\ResourceInterface');

        $cascadingResource = $this->getMockForAbstractClass('MyCLabs\ACL\Model\CascadingResource');
        $cascadingResource->expects($this->once())
            ->method('getSubResources')
            ->will($this->returnValue([ $subResource ]));

        $authorization = $this->getMockBuilder('MyCLabs\ACL\Model\Authorization')
            ->disableOriginalConstructor()
            ->getMock();
        $authorization->expects($this->once())
            ->method('createChildAuthorization')
            ->with($subResource)
            ->will($this->returnValue('foo'));

        $subAuthorizations = $strategy->cascadeAuthorization($authorization, $cascadingResource);

        $this->assertEquals([ 'foo' ], $subAuthorizations);
    }
}
