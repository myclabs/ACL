<?php

namespace MyCLabs\ACL;

use BadMethodCallException;
use Doctrine\ORM\EntityManager;
use InvalidArgumentException;
use MyCLabs\ACL\CascadeStrategy\CascadeStrategy;
use MyCLabs\ACL\CascadeStrategy\SimpleCascadeStrategy;
use MyCLabs\ACL\Model\Actions;
use MyCLabs\ACL\Model\Authorization;
use MyCLabs\ACL\Model\ClassResource;
use MyCLabs\ACL\Model\ResourceInterface;
use MyCLabs\ACL\Model\Role;
use MyCLabs\ACL\Model\RoleEntry;
use MyCLabs\ACL\Model\SecurityIdentityInterface;
use MyCLabs\ACL\Repository\AuthorizationRepository;
use MyCLabs\ACL\Repository\RoleEntryRepository;

/**
 * Manages ACL.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class ACL
{
    /**
     * @var Role[]
     */
    private $roles = [];

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
        /** @var AuthorizationRepository $repository */
        $repository = $this->entityManager->getRepository('MyCLabs\ACL\Model\Authorization');

        return $repository->hasAuthorization($identity, $action, $resource);
    }

    /**
     * Give an authorization from a role to a resource.
     *
     * This method should only be called in roles.
     *
     * @param RoleEntry         $roleEntry
     * @param Actions           $actions
     * @param ResourceInterface $resource
     * @param bool              $cascade  Should the authorization cascade to sub-resources?
     */
    public function allow(RoleEntry $roleEntry, Actions $actions, ResourceInterface $resource, $cascade = true)
    {
        $authorization = Authorization::create($roleEntry, $actions, $resource, $cascade);

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
     *
     * @throws InvalidArgumentException The role doesn't exist.
     * @throws BadMethodCallException No resource given to grant the role, and no resource configured in the role
     * @throws AlreadyHasRoleException The security identity already has this role.
     */
    public function grant(SecurityIdentityInterface $identity, $roleName, ResourceInterface $resource = null)
    {
        $role = $this->getRole($roleName, $resource);

        $resource = $role->validateAndReturnResourceForGrant($resource);

        $this->guardAgainstDuplicateRole($identity, $roleName, $resource);

        $roleEntry = new RoleEntry($identity, $roleName, $resource);
        $identity->addRoleEntry($roleEntry);

        $this->entityManager->persist($roleEntry);
        $this->entityManager->flush($roleEntry);

        $role->createAuthorizations($this, $roleEntry, $resource);
    }

    /**
     * Remove a role from a user.
     *
     * The role deletion will be flushed in database.
     * The authorizations will be automatically removed.
     *
     * @param SecurityIdentityInterface $identity
     * @param string                    $roleName
     * @param ResourceInterface         $resource
     * @throws InvalidArgumentException
     */
    public function revoke(SecurityIdentityInterface $identity, $roleName, ResourceInterface $resource = null)
    {
        $role = $this->getRole($roleName, $resource);

        $resource = $role->validateAndReturnResourceForGrant($resource);

        /** @var RoleEntryRepository $roleEntryRepository */
        $roleEntryRepository = $this->entityManager->getRepository('Myclabs\ACL\Model\RoleEntry');
        $roleEntry = $roleEntryRepository->findOneByIdentityAndRoleAndResource($identity, $roleName, $resource);

        $identity->removeRoleEntry($roleEntry);
        $this->entityManager->remove($roleEntry);

        // Authorizations are deleted in cascade in database
        $this->entityManager->flush($roleEntry);
    }

    /**
     * Check if a security identity is granted a role.
     *
     * @param SecurityIdentityInterface $identity
     * @param string                    $roleName
     * @param ResourceInterface|null    $resource
     *
     * @return bool
     */
    public function isGranted(SecurityIdentityInterface $identity, $roleName, ResourceInterface $resource = null)
    {
        $role = $this->getRole($roleName, $resource);

        $resource = $role->validateAndReturnResourceForGrant($resource);

        /** @var RoleEntryRepository $roleEntryRepository */
        $roleEntryRepository = $this->entityManager->getRepository('Myclabs\ACL\Model\RoleEntry');

        $roleEntry = $roleEntryRepository->findOneByIdentityAndRoleAndResource($identity, $roleName, $resource);

        return ($roleEntry !== null);
    }

    /**
     * Process a new resource that has been persisted.
     *
     * Called by the EntityResourcesListener.
     *
     * @param ResourceInterface $resource
     */
    public function processNewResource(ResourceInterface $resource)
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
     * @param ResourceInterface $resource
     */
    public function processDeletedResource(ResourceInterface $resource)
    {
        /** @var AuthorizationRepository $authorizationRepository */
        $authorizationRepository = $this->entityManager->getRepository('MyCLabs\ACL\Model\Authorization');
        /** @var RoleEntryRepository $roleEntryRepository */
        $roleEntryRepository = $this->entityManager->getRepository('MyCLabs\ACL\Model\RoleEntry');

        // Remove the role entries for this resource
        $roleEntryRepository->removeForResource($resource);

        // Remove the authorizations for this resource
        $authorizationRepository->removeForResource($resource);
    }

    /**
     * Clears and rebuilds all the authorizations from the roles.
     */
    public function rebuildAuthorizations()
    {
        $roleEntryRepository = $this->entityManager->getRepository('MyCLabs\ACL\Model\RoleEntry');

        // Clear
        $this->entityManager->createQuery('DELETE MyCLabs\ACL\Model\Authorization')->execute();
        $this->entityManager->clear('MyCLabs\ACL\Model\Authorization');

        // Regenerate
        foreach ($roleEntryRepository->findAll() as $roleEntry) {
            /** @var RoleEntry $roleEntry */
            $role = $this->getRole($roleEntry->getRoleName());

            // Get the resource from the role entry
            $resourceId = $roleEntry->getResourceId();
            if ($resourceId->getId()) {
                $resource = $this->entityManager->find($resourceId->getName(), $resourceId->getId());
            } else {
                $resource = new ClassResource($resourceId->getName());
            }

            $role->createAuthorizations($this, $roleEntry, $resource);
        }

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    /**
     * Configure the roles using an array.
     *
     * @todo Move in the constructor.
     *
     * @param array $roles
     */
    public function setRoles(array $roles)
    {
        foreach ($roles as $roleName => $roleArray) {
            $this->roles[$roleName] = Role::fromArray($roleName, $roleArray);
        }
    }

    /**
     * @param string $roleName
     * @throws InvalidArgumentException The role doesn't exist.
     * @return Role
     */
    private function getRole($roleName)
    {
        if (! isset($this->roles[$roleName])) {
            throw new InvalidArgumentException(sprintf("The role %s doesn't exist", $roleName));
        }

        return $this->roles[$roleName];
    }

    private function guardAgainstDuplicateRole(
        SecurityIdentityInterface $identity,
        $roleName,
        ResourceInterface $resource
    ) {
        /** @var RoleEntryRepository $roleEntryRepository */
        $roleEntryRepository = $this->entityManager->getRepository('Myclabs\ACL\Model\RoleEntry');
        $roleEntry = $roleEntryRepository->findOneByIdentityAndRoleAndResource($identity, $roleName, $resource);

        if ($roleEntry) {
            throw new AlreadyHasRoleException('The security identity already has this role');
        }
    }
}
