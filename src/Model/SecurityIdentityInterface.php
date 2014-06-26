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
    public function getRoles();

    /**
     * @param RoleEntry $role
     */
    public function addRole(RoleEntry $role);

    /**
     * @param RoleEntry $role
     */
    public function removeRole(RoleEntry $role);
}
