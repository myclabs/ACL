<?php

namespace MyCLabs\ACL\Model;

/**
 * ACL resource.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface ResourceInterface
{
    /**
     * @return ResourceId
     */
    public function getResourceId();
}
