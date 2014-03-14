<?php

namespace MyCLabs\ACL;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use MyCLabs\ACL\Model\Actions;
use MyCLabs\ACL\Model\Authorization;
use MyCLabs\ACL\Model\CascadingResource;
use MyCLabs\ACL\Model\ClassFieldResource;
use MyCLabs\ACL\Model\ClassResource;
use MyCLabs\ACL\Model\EntityFieldResource;
use MyCLabs\ACL\Model\EntityResource;
use MyCLabs\ACL\Model\ResourceInterface;
use MyCLabs\ACL\Model\Role;
use MyCLabs\ACL\Model\SecurityIdentityInterface;
use MyCLabs\ACL\Repository\AuthorizationRepository;

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

    /**
     * @var AuthorizationRepository
     */
    private $authorizationRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->authorizationRepository = $entityManager->getRepository('MyCLabs\ACL\Model\Authorization');
    }

    /**
     * Checks if the identity is allowed to do the action on the resource.
     *
     * @param SecurityIdentityInterface $identity
     * @param string                    $action
     * @param ResourceInterface         $resource
     *
     * @throws \RuntimeException The entity is not persisted (ID must be not null).
     * @return boolean Is allowed, or not.
     */
    public function isAllowed(SecurityIdentityInterface $identity, $action, ResourceInterface $resource)
    {
        if ($resource instanceof EntityResource) {
            return $this->isAllowedOnEntity($identity, $action, $resource);
        } elseif ($resource instanceof ClassResource) {
            return $this->isAllowedOnEntityClass($identity, $action, $resource->getClass());
        } elseif ($resource instanceof EntityFieldResource) {
            return $this->isAllowedOnEntityField($identity, $action, $resource->getEntity(), $resource->getField());
        } elseif ($resource instanceof ClassFieldResource) {
            return $this->isAllowedOnEntityClassField($identity, $action, $resource->getClass(), $resource->getField());
        }

        throw new \RuntimeException('Unknown type of resource: ' . get_class($resource));
    }

    public function allow(Role $role, Actions $actions, ResourceInterface $resource)
    {
        $authorization = Authorization::create($role, $actions, $resource);

        $authorizations = [ $authorization ];

        if ($resource instanceof CascadingResource) {
            foreach ($resource->getSubResources($this->entityManager) as $subResource) {
                $authorizations[] = $authorization->createChildAuthorization($subResource);
            }
        }

        $this->authorizationRepository->insertBulk($authorizations);
    }

    /**
     * Grant a role to a user.
     *
     * The role will be flushed in database.
     * The authorizations related to this role will be automatically created.
     *
     * @param SecurityIdentityInterface $identity
     * @param Role                      $role
     */
    public function grant(SecurityIdentityInterface $identity, Role $role)
    {
        $identity->addRole($role);
        $this->entityManager->persist($role);
        $this->entityManager->flush($role);

        $role->createAuthorizations($this);
    }

    /**
     * Remove a role from a user.
     *
     * The role deletion will be flushed in database.
     * The authorizations will be automatically removed.
     *
     * @param SecurityIdentityInterface $identity
     * @param Role                      $role
     */
    public function unGrant(SecurityIdentityInterface $identity, Role $role)
    {
        $identity->removeRole($role);
        $this->entityManager->remove($role);

        // Authorizations are deleted in cascade in database
        $this->entityManager->flush($role);
    }

    public function processNewResource(EntityResource $entity)
    {
        if (! $entity instanceof CascadingResource) {
            return;
        }

        // Find non cascaded authorizations on the parent resources
        $authorizationsToCascade = [];
        foreach ($this->getAllParentResources($entity) as $parentResource) {
            $authorizationsToCascade = array_merge(
                $authorizationsToCascade,
                $this->authorizationRepository->findNonCascadedAuthorizationsForResource($parentResource)
            );
        }

        $authorizations = [];
        foreach ($authorizationsToCascade as $authorizationToCascade) {
            /** @var Authorization $authorizationToCascade */
            $authorizations[] = $authorizationToCascade->createChildAuthorization($entity);
        }

        $this->authorizationRepository->insertBulk($authorizations);
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
            $role->createAuthorizations($this);
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    private function isAllowedOnEntity(SecurityIdentityInterface $identity, $action, EntityResource $entity)
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
                    AND authorization.entityField IS NULL
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
                    AND authorization.entityField IS NULL
                    AND authorization.securityIdentity = :securityIdentity
                    AND authorization.actions.$action = true";

        $query = $this->entityManager->createQuery($dql);
        $query->setParameter('entityClass', $entityClass);
        $query->setParameter('securityIdentity', $identity);

        return ($query->getSingleScalarResult() > 0);
    }

    private function isAllowedOnEntityField(
        SecurityIdentityInterface $identity,
        $action,
        EntityResource $entity,
        $field
    ) {
        $entityClass = ClassUtils::getClass($entity);

        if ($entity->getId() === null) {
            throw new \RuntimeException(sprintf(
                'The entity resource %s must be persisted (id not null) to be able to test the permissions',
                $entityClass
            ));
        }

        // Check first if the user has access to the field at class-scope
        if ($this->isAllowedOnEntityClassField($identity, $action, $entityClass, $field)) {
            return true;
        }

        $dql = "SELECT count(entity)
                FROM $entityClass entity
                JOIN MyCLabs\\ACL\\Model\\Authorization authorization WITH entity.id = authorization.entityId
                WHERE entity = :entity
                    AND authorization.entityClass = :entityClass
                    AND authorization.entityField = :entityField
                    AND authorization.securityIdentity = :securityIdentity
                    AND authorization.actions.$action = true";

        $query = $this->entityManager->createQuery($dql);
        $query->setParameter('entity', $entity);
        $query->setParameter('entityClass', $entityClass);
        $query->setParameter('entityField', $field);
        $query->setParameter('securityIdentity', $identity);

        return ($query->getSingleScalarResult() > 0);
    }

    private function isAllowedOnEntityClassField(SecurityIdentityInterface $identity, $action, $entityClass, $field)
    {
        $dql = "SELECT count(authorization)
                FROM MyCLabs\\ACL\\Model\\Authorization authorization
                WHERE authorization.entityClass = :entityClass
                    AND authorization.entityField = :entityField
                    AND authorization.securityIdentity = :securityIdentity
                    AND authorization.actions.$action = true";

        $query = $this->entityManager->createQuery($dql);
        $query->setParameter('entityClass', $entityClass);
        $query->setParameter('entityField', $field);
        $query->setParameter('securityIdentity', $identity);

        return ($query->getSingleScalarResult() > 0);
    }

    /**
     * @param CascadingResource $resource
     * @return ResourceInterface[]
     */
    private function getAllParentResources(CascadingResource $resource)
    {
        $parents = [];

        foreach ($resource->getParentResources($this->entityManager) as $parentResource) {
            $parents[] = $parentResource;
            if ($parentResource instanceof CascadingResource) {
                $parents = array_merge($parents, $this->getAllParentResources($parentResource));
            }
        }

        return $parents;
    }
}
