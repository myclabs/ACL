<?php

namespace MyCLabs\ACL\Model;

use Doctrine\Common\Collections\Collection;

/**
 * Security identity trait.
 *
 * This trait needs a $roleEntries attribute.
 *
 * @property RoleEntry[]|Collection $roleEntries
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
trait SecurityIdentityTrait
{
    /**
     * @return RoleEntry[]
     */
    public function getRoleEntries()
    {
        return $this->roleEntries;
    }

    /**
     * @param RoleEntry $roleEntry
     */
    public function addRoleEntry(RoleEntry $roleEntry)
    {
        $this->roleEntries[] = $roleEntry;
    }

    /**
     * @param RoleEntry $roleEntry
     */
    public function removeRoleEntry(RoleEntry $roleEntry)
    {
        $this->roleEntries->removeElement($roleEntry);
    }
}
