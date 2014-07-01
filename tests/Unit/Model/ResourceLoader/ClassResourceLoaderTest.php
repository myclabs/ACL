<?php

namespace Tests\MyCLabs\ACL\Unit\Model;

use MyCLabs\ACL\Model\ClassResource;
use MyCLabs\ACL\Model\ResourceLoader\ClassResourceLoader;
use MyCLabs\ACL\Model\VirtualResource;

/**
 * @covers \MyCLabs\ACL\Model\ResourceLoader\ClassResourceLoader
 */
class ClassResourceLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_should_support_class_resources()
    {
        $resource = new ClassResource(get_class());
        $loader = new ClassResourceLoader();

        $this->assertTrue($loader->supports($resource->getResourceId()));
    }
    /**
     * @test
     */
    public function it_should_not_support_other_resources()
    {
        $resource = new VirtualResource(get_class());
        $loader = new ClassResourceLoader();

        $this->assertFalse($loader->supports($resource->getResourceId()));
    }
}
