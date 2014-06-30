<?php

namespace Tests\MyCLabs\ACL\Unit\Doctrine;

use MyCLabs\ACL\Doctrine\ACLSetup;
use MyCLabs\ACL\Model\SecurityIdentityInterface;

/**
 * @covers \MyCLabs\ACL\Doctrine\ACLSetup
 */
class ACLSetupTest extends \PHPUnit_Framework_TestCase
{
    public function testSetSecurityIdentityClass()
    {
        $user = $this->getMockForAbstractClass(SecurityIdentityInterface::class);

        $loader = new ACLSetup();
        $loader->setSecurityIdentityClass(get_class($user));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The given class doesn't implement SecurityIdentityInterface
     */
    public function testSetInvalidSecurityIdentityClass()
    {
        $loader = new ACLSetup();
        $loader->setSecurityIdentityClass('foo', 'foo');
    }
}
