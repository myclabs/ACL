<?php

namespace MyCLabs\ACL\Model;

/**
 * Resource that cascade authorizations.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface CascadingResource extends ResourceInterface
{
    /**
     * @return Resource[]
     */
    public function getParentResources();

    /**
     * @return Resource[]
     */
    public function getSubResources();
}
