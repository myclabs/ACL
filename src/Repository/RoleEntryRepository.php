<?php

namespace MyCLabs\ACL\Repository;

use Doctrine\ORM\EntityRepository;
use MyCLabs\ACL\Model\Identity;
use MyCLabs\ACL\Model\ResourceInterface;
use MyCLabs\ACL\Model\RoleEntry;

/**
 * Role entries repository.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class RoleEntryRepository extends EntityRepository
{
    /**
     * Find the role entries of the given role that apply to the resource.
     *
     * @param string            $roleName
     * @param ResourceInterface $resource
     *
     * @return RoleEntry[]
     */
    public function findByRoleAndResource($roleName, ResourceInterface $resource)
    {
        $resourceId = $resource->getResourceId();

        return $this->findBy([
            'roleName'      => $roleName,
            'resource.name' => $resourceId->getName(),
            'resource.id'   => $resourceId->getId(),
        ]);
    }

    /**
     * Find a role entry.
     *
     * @param Identity          $identity
     * @param string            $roleName
     * @param ResourceInterface $resource
     *
     * @return RoleEntry|null
     */
    public function findOneByIdentityAndRoleAndResource(
        Identity $identity,
        $roleName,
        ResourceInterface $resource
    ) {
        $resourceId = $resource->getResourceId();

        return $this->findOneBy([
            'identity'      => $identity->getId(),
            'roleName'      => $roleName,
            'resource.name' => $resourceId->getName(),
            'resource.id'   => $resourceId->getId(),
        ]);
    }

    /**
     * Remove the role entries that apply to the given resource.
     *
     * @param ResourceInterface $resource
     *
     * @return RoleEntry[]
     */
    public function removeForResource(ResourceInterface $resource)
    {
        $resourceId = $resource->getResourceId();

        if ($resourceId->getId() !== null) {
            $query = $this->_em->createQuery(
                'DELETE MyCLabs\ACL\Model\RoleEntry r WHERE r.resource.name = ?1 AND r.resource.id = ?2'
            );
            $query->setParameter(1, $resourceId->getName());
            $query->setParameter(2, $resourceId->getId());
        } else {
            $query = $this->_em->createQuery(
                'DELETE MyCLabs\ACL\Model\RoleEntry r WHERE r.resource.name = ?1 AND r.resource.id IS NULL'
            );
            $query->setParameter(1, $resourceId->getName());
        }

        return $query->getResult();
    }
}
