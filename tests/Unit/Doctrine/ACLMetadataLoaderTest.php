<?php

namespace Tests\MyCLabs\ACL\Unit\Doctrine;

use MyCLabs\ACL\Doctrine\ACLMetadataLoader;

/**
 * @covers \MyCLabs\ACL\Doctrine\ACLMetadataLoader
 */
class ACLMetadataLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testRegisterRoleClass()
    {
        $role = $this->getMock('MyCLabs\ACL\Model\Role', [], [], '', false);

        $loader = new ACLMetadataLoader();
        $loader->registerRoleClass(get_class($role), 'foo');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage foo doesn't extend MyCLabs\ACL\Model\Role
     */
    public function testRegisterInvalidRoleClass()
    {
        $loader = new ACLMetadataLoader();
        $loader->registerRoleClass('foo', 'foo');
    }

    public function testSetActionsClass()
    {
        $actions = $this->getMock('MyCLabs\ACL\Model\Actions', [], [], '', false);

        $loader = new ACLMetadataLoader();
        $loader->setActionsClass(get_class($actions));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The given class doesn't extend MyCLabs\ACL\Model\Actions
     */
    public function testSetInvalidActionsClass()
    {
        $loader = new ACLMetadataLoader();
        $loader->setActionsClass('foo');
    }
}
