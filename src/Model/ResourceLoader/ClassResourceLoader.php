<?php

namespace MyCLabs\ACL\Model\ResourceLoader;

use MyCLabs\ACL\Model\ClassResource;
use MyCLabs\ACL\Model\ResourceId;

/**
 * Loads a class resource.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class ClassResourceLoader implements ResourceLoader
{
    public function supports(ResourceId $resourceId)
    {
        return strpos($resourceId->getName(), 'Class:') === 0;
    }

    public function load(ResourceId $resourceId)
    {
        $class = $resourceId->getName();

        return new ClassResource($class);
    }
}
