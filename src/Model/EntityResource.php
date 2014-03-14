<?php

namespace MyCLabs\ACL\Model;

/**
 * Entity being a resource.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface EntityResource extends ResourceInterface, CascadingResource
{
    /**
     * @return mixed
     */
    public function getId();
}
