<?php

namespace MyCLabs\ACL\Model\ResourceLoader;

use MyCLabs\ACL\Model\ResourceId;
use MyCLabs\ACL\Model\ResourceInterface;

/**
 * Loads a resource from its resource ID.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface ResourceLoader
{
    /**
     * Returns true if the loader can load the given resource.
     *
     * @param ResourceId $resourceId
     *
     * @return bool
     */
    public function supports(ResourceId $resourceId);

    /**
     * Returns the resource from its resource ID.
     *
     * @param ResourceId $resourceId
     *
     * @return ResourceInterface
     */
    public function load(ResourceId $resourceId);
}
