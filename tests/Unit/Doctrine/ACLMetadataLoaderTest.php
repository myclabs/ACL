<?php

namespace Tests\MyCLabs\ACL\Unit\Doctrine;

use MyCLabs\ACL\Doctrine\ACLMetadataLoader;
use MyCLabs\ACL\Model\Actions;

/**
 * @covers \MyCLabs\ACL\Doctrine\ACLMetadataLoader
 */
class ACLMetadataLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testSetActionsClass()
    {
        $actions = $this->getMock(Actions::class, [], [], '', false);

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
