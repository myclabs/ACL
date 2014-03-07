<?php

namespace MyCLabs\ACL\Model;

use Doctrine\Common\Collections\Collection;

/**
 * Security identity trait.
 *
 * This trait needs a $roles attribute.
 *
 * @property Role[]|Collection $roles
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
trait SecurityIdentityTrait
{
    /**
     * @return Role[]
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @param Role $role
     */
    public function addRole(Role $role)
    {
        $this->roles[] = $role;
    }

    /**
     * @param Role $role
     */
    public function removeRole(Role $role)
    {
        $this->roles->removeElement($role);
    }
}
