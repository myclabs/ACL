<?php

namespace MyCLabs\ACL\Model;

use Doctrine\ORM\EntityManager;

/**
 * Resource that cascade authorizations.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface CascadingResource extends ResourceInterface
{
    /**
     * @param EntityManager $entityManager
     * @return CascadingResource[]
     */
    public function getParentResources(EntityManager $entityManager);

    /**
     * @param EntityManager $entityManager
     * @return CascadingResource[]
     */
    public function getSubResources(EntityManager $entityManager);
}
