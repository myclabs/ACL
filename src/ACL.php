<?php

namespace MyCLabs\ACL;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use MyCLabs\ACL\CascadeStrategy\CascadeStrategy;
use MyCLabs\ACL\CascadeStrategy\SimpleCascadeStrategy;
use MyCLabs\ACL\Model\Actions;
use MyCLabs\ACL\Model\Authorization;
use MyCLabs\ACL\Model\ClassResource;
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
     * @param Role              $role
     * @param Actions           $actions
     * @param ResourceInterface $resource
     * @param bool              $cascade  Should the authorization cascade to sub-resources?
     */
    public function allow(Role $role, Actions $actions, ResourceInterface $resource, $cascade = true)
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
     * Remove a role from a user.
     *
     * The role deletion will be flushed in database.
     * The authorizations will be automatically removed.
     *
     * @param SecurityIdentityInterface $identity
     * @param string $roleName
     * @param \MyCLabs\ACL\Model\EntityResource $resource
     * @throws \InvalidArgumentException
     */
    public function revoke(SecurityIdentityInterface $identity, $roleName, EntityResource $resource = null)
    {
        if (!array_key_exists($roleName, $this->roles)) {
            throw new \InvalidArgumentException(sprintf(
                'The role name %s doesn\'t exists in the roles list',
                $roleName
            ));
        }
        if (!($this->roles[$roleName]['resource'] instanceof ClassResource)
            && $this->roles[$roleName]['resource'] != ClassUtils::getClass($resource)
        ) {
            throw new \InvalidArgumentException('The given resource class doesn\'t match the role resource class');
        }
        if (null === $resource && !($this->roles[$roleName]['resource'] instanceof ClassResource)) {
            throw new \InvalidArgumentException('The resource is null and the role\'s resource is not a ClassResource');
        }

        $roleRepo = $this->entityManager->getRepository('Myclabs\ACL\Model\Role');

        if ($this->roles[$roleName]['resource'] instanceof ClassResource) {
            /** @var Role $role */
            $role = $roleRepo->findOneBy([ 'securityIdentity' => $identity->getId(), 'name' => $roleName ]);
        }
        else {
            /** @var Role $role */
            $role = $roleRepo->findOneBy([
                'securityIdentity' => $identity->getId(),
                'resourceId' => $resource->getId(),
                'name' => $roleName
            ]);
        }

        $identity->removeRole($role);
        $this->entityManager->remove($role);

        // Authorizations are deleted in cascade in database
        $this->entityManager->flush($role);
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
            $actions = $this->roles[$role->getName()]['actions'];
            $resourceClass = $this->roles[$role->getName()]['resource'];
            $resourceRepo = $this->entityManager->getRepository($resourceClass);
            $resource = $resourceRepo->find($role->getResourceId());
            $this->allow($role, $actions, $resource);
            //$role->createAuthorizations($this);
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    /**
     * Grant a role to a user.
     *
     * The role will be flushed in database.
     * The authorizations related to this role will be automatically created.
     *
     * @param SecurityIdentityInterface $identity
     * @param string $roleName
     * @param Model\ResourceInterface $resource
     * @throws \InvalidArgumentException
     */
    public function grant(SecurityIdentityInterface $identity, $roleName, ResourceInterface $resource = null)
    {
        if (!array_key_exists($roleName, $this->roles)) {
            throw new \InvalidArgumentException(sprintf(
                'The role name %s doesn\'t exists in the roles list',
                $roleName
            ));
        }
        if (!($this->roles[$roleName]['resource'] instanceof ClassResource)
            && $this->roles[$roleName]['resource'] != ClassUtils::getClass($resource)
        ) {
            throw new \InvalidArgumentException('The given resource class doesn\'t match the role resource class');
        }
        if (null === $resource && !($this->roles[$roleName]['resource'] instanceof ClassResource)) {
            throw new \InvalidArgumentException('The resource is null and the role\'s resource is not a ClassResource');
        }
        if ($this->roles[$roleName]['resource'] instanceof ClassResource) {
            $resource = $this->roles[$roleName]['resource'];
        }
        $role = new Role($identity, $roleName, $resource);

        $identity->addRole($role);

        $this->entityManager->persist($role);
        $this->entityManager->flush($role);

        $this->allow($role, $this->roles[$roleName]['actions'], $resource);
    }

    public function setRoles($roles)
    {
        $this->roles = $roles;
    }
}
