<?php

namespace MyCLabs\ACL\Model\Repository;

use MyCLabs\ACL\Model\Authorization;
use MyCLabs\ACL\Model\Identity;
use MyCLabs\ACL\Model\ResourceInterface;

/**
 * Authorization repository.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface AuthorizationRepository
{
    /**
     * Insert authorizations directly in database without using the entity manager.
     *
     * This is much more optimized than using the entity manager.
     * This methods inserts in batch of 1000 inserts, each batch being in a transaction. It is to
     * avoid locking the authorizations table for too long, which could impact other web requests.
     *
     * @param Authorization[] $authorizations
     * @throws \RuntimeException Parent authorizations in the array must appear before their children.
     */
    public function insertBulk(array $authorizations);

    /**
     * Check if there is at least one authorization for the given identity, action and resource.
     *
     * @param Identity          $identity
     * @param string            $action
     * @param ResourceInterface $resource
     *
     * @return boolean There is an authorization or not.
     */
    public function hasAuthorization(Identity $identity, $action, ResourceInterface $resource);

    /**
     * Returns authorization for the given resource that are cascadable to sub-resources,
     * i.e. they are "cascadable" and have no parent authorization (we only want "root" authorizations).
     *
     * @param ResourceInterface $resource
     *
     * @return Authorization[]
     */
    public function findCascadableAuthorizationsForResource(ResourceInterface $resource);

    /**
     * Remove all the authorizations that apply to the given resource.
     *
     * @param ResourceInterface $resource
     * @throws \RuntimeException If the resource is an entity, it must be persisted.
     */
    public function removeForResource(ResourceInterface $resource);
}
