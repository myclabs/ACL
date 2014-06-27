<?php

namespace MyCLabs\ACL\Repository;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityRepository;
use MyCLabs\ACL\Model\ClassResource;
use MyCLabs\ACL\Model\EntityResource;
use MyCLabs\ACL\Model\ResourceInterface;
use MyCLabs\ACL\Model\RoleEntry;
use MyCLabs\ACL\Model\SecurityIdentityInterface;

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
        if ($resource instanceof EntityResource) {
            return $this->findOneBy([
                'roleName'         => $roleName,
                'entityClass'      => ClassUtils::getClass($resource),
                'entityId'         => $resource->getId(),
            ]);
        }

        /** @var ClassResource $resource */
        return $this->findBy([
            'roleName'         => $roleName,
            'entityClass'      => $resource->getClass(),
            'entityId'         => null,
        ]);
    }

    /**
     * Find a role entry.
     *
     * @param SecurityIdentityInterface $identity
     * @param string                    $roleName
     * @param ResourceInterface         $resource
     *
     * @return RoleEntry|null
     */
    public function findOneByIdentityAndRoleAndResource(
        SecurityIdentityInterface $identity,
        $roleName,
        ResourceInterface $resource
    ) {
        if ($resource instanceof EntityResource) {
            return $this->findOneBy([
                'securityIdentity' => $identity->getId(),
                'roleName'         => $roleName,
                'entityClass'      => ClassUtils::getClass($resource),
                'entityId'         => $resource->getId(),
            ]);
        }

        /** @var ClassResource $resource */
        return $this->findOneBy([
            'securityIdentity' => $identity->getId(),
            'roleName'         => $roleName,
            'entityClass'      => $resource->getClass(),
            'entityId'         => null,
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
        if ($resource instanceof EntityResource) {
            $query = $this->_em->createQuery(
                'DELETE MyCLabs\ACL\Model\RoleEntry r WHERE r.entityClass = ?1 AND r.entityId = ?2'
            );
            $query->setParameter(1, ClassUtils::getClass($resource));
            $query->setParameter(2, $resource->getId());
        } else {
            /** @var ClassResource $resource */
            $query = $this->_em->createQuery(
                'DELETE MyCLabs\ACL\Model\RoleEntry r WHERE r.entityClass = ?1 AND r.entityId IS NULL'
            );
            $query->setParameter(1, $resource->getClass());
        }

        return $query->getResult();
    }
}
