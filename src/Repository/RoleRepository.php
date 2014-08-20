<?php

namespace MyCLabs\ACL\Repository;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityRepository;
use MyCLabs\ACL\Model\Role;
use MyCLabs\ACL\Model\ClassResource;
use MyCLabs\ACL\Model\EntityResource;
use MyCLabs\ACL\Model\ResourceInterface;

/**
 * Authorizations repository.
 *
 * @author Valentin Claras <dev.myclabs.acl@valentin.claras.fr>
 */
class RoleRepository extends EntityRepository
{
    /**
     * Returns Roles that are directly linked to the given resource.
     *
     * @param ResourceInterface $resource
     * @return Role[]
     */
    public function findRolesDirectlyLinkedToResource(ResourceInterface $resource)
    {
        $qb = $this->createQueryBuilder('role');

        // Join
        $qb->join('role.authorizations', 'a');

        // Root authorizations means they are attached to the given resource
        $qb->andWhere('a.parentAuthorization IS NULL');

        if ($resource instanceof EntityResource) {
            $qb->andWhere('a.entityClass = :entityClass');
            $qb->andWhere('a.entityId = :entityId');
            $qb->setParameter('entityClass', ClassUtils::getClass($resource));
            $qb->setParameter('entityId', $resource->getId());
        }
        if ($resource instanceof ClassResource) {
            $qb->andWhere('a.entityClass = :entityClass');
            $qb->andWhere('a.entityId IS NULL');
            $qb->setParameter('entityClass', $resource->getClass());
        }

        return $qb->getQuery()->getResult();
    }
}
