<?php

namespace MyCLabs\ACL;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use InvalidArgumentException;
use MyCLabs\ACL\CascadeStrategy\CascadeStrategy;
use MyCLabs\ACL\CascadeStrategy\SimpleCascadeStrategy;
use MyCLabs\ACL\Model\Actions;
use MyCLabs\ACL\Model\Authorization;
use MyCLabs\ACL\Model\ClassResource;
use MyCLabs\ACL\Model\EntityResource;
use MyCLabs\ACL\Model\ResourceInterface;
use MyCLabs\ACL\Model\RoleEntry;
use MyCLabs\ACL\Model\SecurityIdentityInterface;
use MyCLabs\ACL\Repository\AuthorizationRepository;

/**
 * Manages ACL.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class ACL
{
    /**
     * @var array
     */
    protected $roles;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var CascadeStrategy
     */
    private $cascadeStrategy;

    /**
     * @param EntityManager        $entityManager
     * @param CascadeStrategy|null $cascadeStrategy The strategy to use for cascading authorizations.
     */
    public function __construct(EntityManager $entityManager, CascadeStrategy $cascadeStrategy = null)
    {
        $this->entityManager = $entityManager;

        $this->cascadeStrategy = $cascadeStrategy ?: new SimpleCascadeStrategy($entityManager);
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
        /** @var AuthorizationRepository $repo */
        $repo = $this->entityManager->getRepository('MyCLabs\ACL\Model\Authorization');

        if ($resource instanceof EntityResource) {
            return $repo->isAllowedOnEntity($identity, $action, $resource);
        } elseif ($resource instanceof ClassResource) {
            return $repo->isAllowedOnEntityClass($identity, $action, $resource->getClass());
        }

        throw new \RuntimeException('Unknown type of resource: ' . get_class($resource));
    }

    /**
     * Give an authorization from a role to a resource.
     *
     * This method should only be called in roles.
     *
     * @param RoleEntry              $role
     * @param Actions           $actions
     * @param ResourceInterface $resource
     * @param bool              $cascade  Should the authorization cascade to sub-resources?
     */
    public function allow(RoleEntry $role, Actions $actions, ResourceInterface $resource, $cascade = true)
    {
        $authorization = Authorization::create($role, $actions, $resource, $cascade);

        if ($cascade) {
            $cascadedAuthorizations = $this->cascadeStrategy->cascadeAuthorization($authorization, $resource);

            $authorizations = array_merge([$authorization], $cascadedAuthorizations);
        } else {
            $authorizations = [ $authorization ];
        }

        /** @var AuthorizationRepository $repository */
        $repository = $this->entityManager->getRepository('MyCLabs\ACL\Model\Authorization');

        $repository->insertBulk($authorizations);
    }

    /**
     * Grant a role to a security identity.
     *
     * The role will be flushed in database.
     * The authorizations related to this role will be automatically created.
     *
     * @param SecurityIdentityInterface $identity
     * @param string $roleName
     * @param ResourceInterface $resource
     * @throws InvalidArgumentException
     * @throws AlreadyExistsException
     */
    public function grant(SecurityIdentityInterface $identity, $roleName, ResourceInterface $resource = null)
    {
        $this->checkGrantAndRevokeParameters($roleName, $resource);

        if ($this->roles[$roleName]['resource'] instanceof ClassResource) {
            $resource = $this->roles[$roleName]['resource'];
        }
        if (null !== $this->getRole($identity, $roleName, $resource)) {
            throw new AlreadyExistsException('The role already exists for the specified user and the specified resource');
        }
        $role = new RoleEntry($identity, $roleName, $resource);

        $identity->addRole($role);

        $this->entityManager->persist($role);
        $this->entityManager->flush($role);

        $this->allow($role, $this->roles[$roleName]['actions'], $resource);
    }

    /**
     * Remove a role from a user.
     *
     * The role deletion will be flushed in database.
     * The authorizations will be automatically removed.
     *
     * @param SecurityIdentityInterface $identity
     * @param string $roleName
     * @param EntityResource $resource
     * @throws InvalidArgumentException
     */
    public function revoke(SecurityIdentityInterface $identity, $roleName, EntityResource $resource = null)
    {
        $this->checkGrantAndRevokeParameters($roleName, $resource);

        $role = $this->getRole($identity, $roleName, $resource);

        $identity->removeRole($role);
        $this->entityManager->remove($role);

        // Authorizations are deleted in cascade in database
        $this->entityManager->flush($role);
    }

    /**
     * Check the parameters for revoke and grant
     *
     * @param $roleName
     * @param ResourceInterface $resource
     * @throws InvalidArgumentException
     */
    protected function checkGrantAndRevokeParameters($roleName, ResourceInterface $resource = null)
    {
        if (!array_key_exists($roleName, $this->roles)) {
            throw new InvalidArgumentException(sprintf(
                "The role name %s doesn't exists in the roles list",
                $roleName
            ));
        }
        if (!($this->roles[$roleName]['resource'] instanceof ClassResource)) {
            if (null === $resource) {
                throw new InvalidArgumentException("The resource is null and the role's resource is not a ClassResource");
            }
            if ($this->roles[$roleName]['resource'] != ClassUtils::getClass($resource)) {
                throw new InvalidArgumentException("The given resource class doesn't match the role resource class");
            }
        }
    }

    /**
     * Process a new resource that has been persisted.
     *
     * Called by the EntityResourcesListener.
     *
     * @param EntityResource $resource
     */
    public function processNewResource(EntityResource $resource)
    {
        $cascadedAuthorizations = $this->cascadeStrategy->processNewResource($resource);

        /** @var AuthorizationRepository $repository */
        $repository = $this->entityManager->getRepository('MyCLabs\ACL\Model\Authorization');

        $repository->insertBulk($cascadedAuthorizations);
    }

    /**
     * Process a resource that has been deleted.
     *
     * Called by the EntityResourcesListener.
     *
     * @param EntityResource $resource
     */
    public function processDeletedResource(EntityResource $resource)
    {
        /** @var AuthorizationRepository $repository */
        $repository = $this->entityManager->getRepository('MyCLabs\ACL\Model\Authorization');
        $roleRepo = $this->entityManager->getRepository('MyCLabs\ACL\Model\RoleEntry');

        // Remove the roles for this resource
        foreach ($this->getRolesForResource($resource) as $roleName) {
            $roles = $roleRepo->findBy([
                'resourceId' => $resource->getId(),
                'name' => $roleName
            ]);

            foreach ($roles as $role) {
                $this->entityManager->remove($role);
            }
        }

        // Delete the authorizations for this resource
        $repository->removeAuthorizationsForResource($resource);
    }

    /**
     * Clears and rebuilds all the authorizations from the roles.
     */
    public function rebuildAuthorizations()
    {
        $roleRepository = $this->entityManager->getRepository('MyCLabs\ACL\Model\RoleEntry');

        // Clear
        $this->entityManager->createQuery('DELETE MyCLabs\ACL\Model\Authorization')->execute();
        $this->entityManager->clear('MyCLabs\ACL\Model\Authorization');

        // Regenerate
        foreach ($roleRepository->findAll() as $role) {
            /** @var RoleEntry $role */
            $actions = $this->roles[$role->getName()]['actions'];
            $resourceClass = $this->roles[$role->getName()]['resource'];
            if ($resourceClass instanceof ClassResource) {
                $this->allow($role, $actions, $resourceClass);
            } else {
                $resourceRepo = $this->entityManager->getRepository($resourceClass);
                /** @var EntityResource $resource */
                $resource = $resourceRepo->find($role->getResourceId());
                $this->allow($role, $actions, $resource);
            }
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
    }


    /**
     * Return a specific role given an identity, a rolename and a resource
     *
     * @param SecurityIdentityInterface $identity
     * @param $roleName
     * @param ResourceInterface $resource
     * @return RoleEntry
     */
    protected function getRole(SecurityIdentityInterface $identity, $roleName, ResourceInterface $resource = null)
    {
        $roleRepo = $this->entityManager->getRepository('Myclabs\ACL\Model\RoleEntry');
        if ($this->roles[$roleName]['resource'] instanceof ClassResource) {
            return $roleRepo->findOneBy([ 'securityIdentity' => $identity->getId(), 'name' => $roleName ]);
        } else {
            /** @var EntityResource $resource */
            return $roleRepo->findOneBy([
                'securityIdentity' => $identity->getId(),
                'resourceId' => $resource->getId(),
                'name' => $roleName
            ]);
        }
    }

    public function setRoles($roles)
    {
        $this->roles = $roles;
    }

    /**
     * @param EntityResource $entity
     * @return array
     */
    public function getRolesForResource(EntityResource $entity)
    {
        $roleNames = [];
        $className = ClassUtils::getClass($entity);

        foreach ($this->roles as $roleName => $role) {
            if (isset($role['resource']) && $role['resource'] === $className) {
                $roleNames[] = $roleName;
            }
        }
        return $roleNames;
    }
}
