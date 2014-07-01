<?php

namespace MyCLabs\ACL\Model;

/**
 * ACL identity.
 *
 * @see IdentityTrait
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface Identity
{
    /**
     * @return mixed
     */
    public function getId();

    /**
     * @return RoleEntry[]
     */
    public function getRoleEntries();

    /**
     * @param RoleEntry $roleEntry
     */
    public function addRoleEntry(RoleEntry $roleEntry);

    /**
     * @param RoleEntry $roleEntry
     */
    public function removeRoleEntry(RoleEntry $roleEntry);
}
