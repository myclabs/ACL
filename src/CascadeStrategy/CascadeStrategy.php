<?php

namespace MyCLabs\ACL\CascadeStrategy;

use MyCLabs\ACL\Model\Authorization;
use MyCLabs\ACL\Model\ResourceInterface;

/**
 * Strategy that defines the cascade of authorizations between resources.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface CascadeStrategy
{
    /**
     * @param Authorization     $authorization
     * @param ResourceInterface $resource
     * @return Authorization[]
     */
    public function cascadeAuthorization(Authorization $authorization, ResourceInterface $resource);

    /**
     * @param ResourceInterface $resource
     * @return Authorization[]
     */
    public function processNewResource(ResourceInterface $resource);
}
