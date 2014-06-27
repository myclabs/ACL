<?php

namespace MyCLabs\ACL\Model;

/**
 * Security identity.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface SecurityIdentityInterface
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
