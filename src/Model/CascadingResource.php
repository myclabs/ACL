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
     * @return CascadingResource[]
     */
    public function getParentResources();

    /**
     * @return CascadingResource[]
     */
    public function getSubResources();
}
