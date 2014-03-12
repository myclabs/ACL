<?php

namespace MyCLabs\ACL;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use MyCLabs\ACL\Model\Authorization;
use MyCLabs\ACL\Model\EntityResourceInterface;
use MyCLabs\ACL\Model\Resource;
use MyCLabs\ACL\Model\Role;
use MyCLabs\ACL\Model\SecurityIdentityInterface;

/**
 * Manages ACL.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class ACLManager
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;

        // Register the event listener
        $entityManager->getEventManager()->addEventSubscriber(new EntityManagerListener($this));
    }

    /**
     * Checks if the identity is allowed to do the action on the resource.
     *
     * @param SecurityIdentityInterface        $identity
     * @param string                           $action
     * @param Resource|EntityResourceInterface $resource Resource expected, but an entity can be directly given too.
     *
     * @throws \RuntimeException The entity is not persisted (ID must be not null).
     * @return boolean Is allowed, or not.
     */
    public function isAllowed(SecurityIdentityInterface $identity, $action, $resource)
    {
        if (! $resource instanceof Resource) {
            return $this->isAllowedOnEntity($identity, $action, $resource);
        } elseif ($resource->isEntity()) {
            return $this->isAllowedOnEntity($identity, $action, $resource->getEntity());
        } elseif ($resource->isEntityClass()) {
            return $this->isAllowedOnEntityClass($identity, $action, $resource->getEntityClass());
        }
    }

    public function grant(SecurityIdentityInterface $identity, Role $role)
    {
        $identity->addRole($role);
        $this->entityManager->persist($role);
        $this->entityManager->flush($role);

        $this->persistAuthorizations($role->createAuthorizations($this->entityManager));
    }

    public function processNewResource(EntityResourceInterface $entity)
    {
        // Cascade the authorizations that are a class-scope
        $dql = 'SELECT a FROM MyCLabs\ACL\Model\Authorization a
                WHERE a.entityClass = :entityClass
                AND a.entityId IS NULL';
        $query = $this->entityManager->createQuery($dql);
        $query->setParameter('entityClass', ClassUtils::getClass($entity));

        $authorizations = [];
        foreach ($query->getResult() as $rootAuthorization) {
            /** @var Authorization $rootAuthorization */
            $authorizations[] = $rootAuthorization->createChildAuthorization(Resource::fromEntity($entity));
        }

        $this->persistAuthorizations($authorizations);
    }

    /**
     * Clears and rebuilds all the authorizations from the roles.
     */
    public function rebuildAuthorizations()
    {
        $authorizationRepository = $this->entityManager->getRepository('MyCLabs\ACL\Model\Authorization');
        $roleRepository = $this->entityManager->getRepository('MyCLabs\ACL\Model\Role');

        // Clear
        // TODO use DQL DELETE query
        foreach ($authorizationRepository->findAll() as $authorization) {
            $this->entityManager->remove($authorization);
        }
        $this->entityManager->flush();
        $this->entityManager->clear();

        // Regenerate
        foreach ($roleRepository->findAll() as $role) {
            /** @var Role $role */
            foreach ($role->createAuthorizations($this->entityManager) as $authorization) {
                $this->entityManager->persist($authorization);
            }
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    private function isAllowedOnEntity(SecurityIdentityInterface $identity, $action, EntityResourceInterface $entity)
    {
        $entityClass = ClassUtils::getClass($entity);

        if ($entity->getId() === null) {
            throw new \RuntimeException(sprintf(
                'The entity resource %s must be persisted (id not null) to be able to test the permissions',
                $entityClass
            ));
        }

        $dql = "SELECT count(entity)
                FROM $entityClass entity
                JOIN MyCLabs\\ACL\\Model\\Authorization authorization WITH entity.id = authorization.entityId
                WHERE entity = :entity
                    AND authorization.entityClass = :entityClass
                    AND authorization.securityIdentity = :securityIdentity
                    AND authorization.actions.$action = true";

        $query = $this->entityManager->createQuery($dql);
        $query->setParameter('entity', $entity);
        $query->setParameter('entityClass', $entityClass);
        $query->setParameter('securityIdentity', $identity);

        return ($query->getSingleScalarResult() > 0);
    }

    private function isAllowedOnEntityClass(SecurityIdentityInterface $identity, $action, $entityClass)
    {
        $dql = "SELECT count(authorization)
                FROM MyCLabs\\ACL\\Model\\Authorization authorization
                WHERE authorization.entityClass = :entityClass
                    AND authorization.securityIdentity = :securityIdentity
                    AND authorization.actions.$action = true";

        $query = $this->entityManager->createQuery($dql);
        $query->setParameter('entityClass', $entityClass);
        $query->setParameter('securityIdentity', $identity);

        return ($query->getSingleScalarResult() > 0);
    }

    /**
     * Persist authorizations directly in database without using the entity manager.
     *
     * This is much more optimized than using the entity manager.
     * This methods inserts in batch of 1000 inserts, each batch being in a transaction. It is to
     * avoid locking the authorizations table for too long, which could impact other web requests.
     *
     * @param Authorization[] $authorizations
     * @throws \RuntimeException Parent authorizations in the array must appear before their children.
     */
    public function persistAuthorizations(array $authorizations)
    {
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        $tableName = $this->entityManager->getClassMetadata('MyCLabs\ACL\Model\Authorization')->getTableName();

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
                'role_id'                => $authorization->getRole()->getId(),
                'securityIdentity_id'    => $authorization->getSecurityIdentity()->getId(),
                'parentAuthorization_id' => $parent ? $parent->getId() : null,
                'entity_class'           => $authorization->getEntityClass(),
                'entity_id'              => $authorization->getEntityId(),
            ];

            foreach ($authorization->getActions()->toArray() as $action => $value) {
                $data['actions_' . $action] = $value;
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
}
