<?php

namespace MyCLabs\ACL\Model;

/**
 * Entity being a resource.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface EntityResourceInterface extends ResourceInterface
{
    /**
     * @return mixed
     */
    public function getId();
}
