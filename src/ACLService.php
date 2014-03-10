<?php

namespace MyCLabs\ACL;

use Doctrine\ORM\EntityManager;
use MyCLabs\ACL\Model\ResourceInterface;
use MyCLabs\ACL\Model\Role;
use MyCLabs\ACL\Model\SecurityIdentityInterface;

/**
 * Service handling ACL.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class ACLService
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
     * @param SecurityIdentityInterface $identity
     * @param string                    $action
     * @param ResourceInterface         $resource
     *
     * @return boolean Is allowed, or not.
     */
    public function isAllowed(SecurityIdentityInterface $identity, $action, ResourceInterface $resource)
    {
        return $resource->isAllowed($identity, $action);
    }

    /**
     * Ajoute un role à un utilisateur.
     *
     * @param SecurityIdentityInterface $identity
     * @param Role $role
     */
    public function addRole(SecurityIdentityInterface $identity, Role $role)
    {
        $identity->addRole($role);
        $this->entityManager->persist($role);

        foreach ($role->createAuthorizations($this->entityManager) as $authorization) {
            $this->entityManager->persist($authorization);
        }
    }

    /**
     * Retire un role d'un utilisateur.
     *
     * @param SecurityIdentityInterface $identity
     * @param Role $role
     */
    public function removeRole(SecurityIdentityInterface $identity, Role $role)
    {
        $identity->removeRole($role);
        $this->entityManager->remove($role);
    }

    public function processNewResource(ResourceInterface $resource)
    {
        // TODO improve this
        $roleRepository = $this->entityManager->getRepository('MyCLabs\ACL\Model\Role');

        foreach ($roleRepository->findAll() as $role) {
            /** @var Role $role */
            foreach ($role->processNewResource($resource) as $authorization) {
                $this->entityManager->persist($authorization);
            }
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
}
