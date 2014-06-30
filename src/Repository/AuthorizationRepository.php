<?php

namespace MyCLabs\ACL\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use MyCLabs\ACL\Model\Authorization;
use MyCLabs\ACL\Model\ResourceInterface;
use MyCLabs\ACL\Model\SecurityIdentityInterface;

/**
 * Authorizations repository.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class AuthorizationRepository extends EntityRepository
{
    /**
     * Insert authorizations directly in database without using the entity manager.
     *
     * This is much more optimized than using the entity manager.
     * This methods inserts in batch of 1000 inserts, each batch being in a transaction. It is to
     * avoid locking the authorizations table for too long, which could impact other web requests.
     *
     * @param Authorization[] $authorizations
     * @throws \RuntimeException Parent authorizations in the array must appear before their children.
     */
    public function insertBulk(array $authorizations)
    {
        $connection = $this->getEntityManager()->getConnection();
        $connection->beginTransaction();

        $tableName = $this->getClassMetadata()->getTableName();

        $i = 0;

        foreach ($authorizations as $authorization) {
            // Check parent authorization is persisted
            $parent = $authorization->getParentAuthorization();
            if ($parent !== null && $parent->getId() === null) {
                throw new \RuntimeException(
                    'An authorization has a parent with no ID. Parent authorizations should appear before their'
                    . ' children in the authorizations array so that they can be persisted first (to have an ID)'
                );
            }

            $data = [
                'role_entry_id'          => $authorization->getRoleEntry()->getId(),
                'securityIdentity_id'    => $authorization->getSecurityIdentity()->getId(),
                'parentAuthorization_id' => $parent ? $parent->getId() : null,
                'resource_name'          => $authorization->getResourceId()->getName(),
                'resource_id'            => $authorization->getResourceId()->getId(),
                'cascadable'             => (int) $authorization->isCascadable(),
            ];

            foreach ($authorization->getActions()->toArray() as $action => $value) {
                $data['actions_' . $action] = (int) $value;
            }

            $connection->insert($tableName, $data);

            // Set authorization ID (used if parent of other authorizations to be inserted)
            $authorization->setId($connection->lastInsertId());

            // Commit every 1000 inserts to avoid locking the table too long
            if (($i % 1000) === 0) {
                $connection->commit();
                $connection->beginTransaction();
            }

            $i++;
        }

        $connection->commit();
    }

    /**
     * Check if there is at least one authorization for the given identity, action and resource.
     *
     * @param SecurityIdentityInterface $identity
     * @param string                    $action
     * @param ResourceInterface         $resource
     *
     * @return boolean There is an authorization or not.
     */
    public function hasAuthorization(SecurityIdentityInterface $identity, $action, ResourceInterface $resource)
    {
        $qb = $this->createQueryBuilder('a');
        $qb->select('COUNT(a)');

        $qb->andWhere('a.securityIdentity = :securityIdentity');
        $qb->andWhere("a.actions.$action = true");
        $qb->setParameter('securityIdentity', $identity);

        $this->filterQueryWithResource($qb, $resource);

        return ($qb->getQuery()->getSingleScalarResult() > 0);
    }

    /**
     * Returns authorization for the given resource that are cascadable to sub-resources,
     * i.e. they are "cascadable" and have no parent authorization (we only want "root" authorizations).
     *
     * @param ResourceInterface $resource
     * @return Authorization[]
     */
    public function findCascadableAuthorizationsForResource(ResourceInterface $resource)
    {
        $qb = $this->createQueryBuilder('a');

        // Cascadable
        $qb->where('a.cascadable = true');

        // Root authorizations means no parent
        $qb->andWhere('a.parentAuthorization IS NULL');

        $this->filterQueryWithResource($qb, $resource);

        return $qb->getQuery()->getResult();
    }

    /**
     * Remove all the authorizations that apply to the given resource.
     *
     * @param ResourceInterface $resource
     * @throws \RuntimeException If the resource is an entity, it must be persisted.
     */
    public function removeForResource(ResourceInterface $resource)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb->delete($this->getEntityName(), 'a');

        $this->filterQueryWithResource($qb, $resource);

        $qb->getQuery()->execute();
    }

    private function filterQueryWithResource(QueryBuilder $query, ResourceInterface $resource)
    {
        $resourceId = $resource->getResourceId();

        $query->andWhere('a.resource.name = :resourceName');
        $query->setParameter('resourceName', $resourceId->getName());

        if ($resourceId->getId() !== null) {
            $query->andWhere('a.resource.id = :resourceId');
            $query->setParameter('resourceId', $resourceId->getId());
        } else {
            $query->andWhere('a.resource.id IS NULL');
        }
    }
}
