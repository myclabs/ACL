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
     * @return Role[]
     */
    public function getRoles();

    /**
     * @param Role $role
     */
    public function addRole(Role $role);

    /**
     * @param Role $role
     */
    public function removeRole(Role $role);
}
