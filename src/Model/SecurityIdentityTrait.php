<?php

namespace MyCLabs\ACL\Model;

use Doctrine\Common\Collections\Collection;

/**
 * Security identity trait.
 *
 * This trait needs a $roles attribute.
 *
 * @property RoleEntry[]|Collection $roles
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
trait SecurityIdentityTrait
{
    /**
     * @return RoleEntry[]
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @param RoleEntry $role
     */
    public function addRole(RoleEntry $role)
    {
        $this->roles[] = $role;
    }

    /**
     * @param RoleEntry $role
     */
    public function removeRole(RoleEntry $role)
    {
        $this->roles->removeElement($role);
    }
}
