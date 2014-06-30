<?php

namespace MyCLabs\ACL\Model;

use Doctrine\Common\Util\ClassUtils;

/**
 * Helper for implementing ResourceInterface in entities.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
trait EntityResourceTrait
{
    /**
     * @return int
     */
    public abstract function getId();

    /**
     * @return ResourceId
     */
    public function getResourceId()
    {
        return new ResourceId(ClassUtils::getClass($this), $this->getId());
    }
}
