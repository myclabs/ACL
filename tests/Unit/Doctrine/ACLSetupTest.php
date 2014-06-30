<?php

namespace Tests\MyCLabs\ACL\Unit\Doctrine;

use MyCLabs\ACL\Doctrine\ACLSetup;
use MyCLabs\ACL\Model\Identity;

/**
 * @covers \MyCLabs\ACL\Doctrine\ACLSetup
 */
class ACLSetupTest extends \PHPUnit_Framework_TestCase
{
    public function testSetIdentityClass()
    {
        $user = $this->getMockForAbstractClass(Identity::class);

        $loader = new ACLSetup();
        $loader->setIdentityClass(get_class($user));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The given class doesn't implement the Identity interface
     */
    public function testSetInvalidIdentityClass()
    {
        $loader = new ACLSetup();
        $loader->setIdentityClass('foo', 'foo');
    }
}
