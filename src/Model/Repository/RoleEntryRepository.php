<?php

namespace MyCLabs\ACL\Model\Repository;

use MyCLabs\ACL\Model\Identity;
use MyCLabs\ACL\Model\ResourceInterface;
use MyCLabs\ACL\Model\RoleEntry;

/**
 * Role entries repository.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface RoleEntryRepository
{
    /**
     * Find the role entries of the given role that apply to the resource.
     *
     * @param string            $roleName
     * @param ResourceInterface $resource
     *
     * @return RoleEntry[]
     */
    public function findByRoleAndResource($roleName, ResourceInterface $resource);

    /**
     * Find a role entry.
     *
     * @param Identity          $identity
     * @param string            $roleName
     * @param ResourceInterface $resource
     *
     * @return RoleEntry|null
     */
    public function findOneByIdentityAndRoleAndResource(Identity $identity, $roleName, ResourceInterface $resource);

    /**
     * Remove the role entries that apply to the given resource.
     *
     * @param ResourceInterface $resource
     *
     * @return RoleEntry[]
     */
    public function removeForResource(ResourceInterface $resource);
}
