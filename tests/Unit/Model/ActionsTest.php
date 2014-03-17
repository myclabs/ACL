<?php

namespace Tests\MyCLabs\ACL\Unit\Model;

use MyCLabs\ACL\Model\Actions;

/**
 * @covers \MyCLabs\ACL\Model\Actions
 */
class ActionsTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $actions = new Actions([
            Actions::VIEW,
            Actions::EDIT,
        ]);

        $this->assertTrue($actions->view);
        $this->assertTrue($actions->edit);
        $this->assertFalse($actions->create);
        $this->assertFalse($actions->delete);
        $this->assertFalse($actions->undelete);
        $this->assertFalse($actions->allow);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unknown ACL action foo
     */
    public function testUnknownAction()
    {
        new Actions(['foo']);
    }

    public function testToArray()
    {
        $actions = new Actions([
            Actions::VIEW,
            Actions::EDIT,
        ]);

        $this->assertEquals([
            Actions::VIEW => true,
            Actions::EDIT => true,
            Actions::CREATE => false,
            Actions::DELETE => false,
            Actions::UNDELETE => false,
            Actions::ALLOW => false,
        ], $actions->toArray());
    }

    public function testAll()
    {
        $actions = Actions::all();

        $this->assertTrue($actions->view);
        $this->assertTrue($actions->edit);
        $this->assertTrue($actions->create);
        $this->assertTrue($actions->delete);
        $this->assertTrue($actions->undelete);
        $this->assertTrue($actions->allow);
    }
}
