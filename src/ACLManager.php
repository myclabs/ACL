<?php

namespace MyCLabs\ACL;

use Doctrine\ORM\EntityManager;
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
    }

    /**
     * Checks if the identity is allowed to do the action on the resource.
     *
     * @param SecurityIdentityInterface        $identity
     * @param string                           $action
     * @param Resource|EntityResourceInterface $resource Resource expected, but an entity can be directly given too.
     *
     * @throws \RuntimeException The resource is not persisted (ID must be not null).
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
        if ($entity->getId() === null) {
            throw new \RuntimeException(sprintf(
                'The entity resource %s must be persisted (id not null) to be able to test the permissions',
                get_class($entity)
            ));
        }

        $entityClass = get_class($entity);
        $dql = "SELECT count(entity)
                FROM $entityClass entity
                INNER JOIN entity.authorizations authorization
                WHERE entity = :entity
                    AND authorization.securityIdentity = :securityIdentity
                    AND authorization.actions.$action = true";

        $query = $this->entityManager->createQuery($dql);
        $query->setParameter('entity', $entity);
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
}
