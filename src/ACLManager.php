<?php

namespace MyCLabs\ACL;

use Doctrine\ORM\EntityManager;
use MyCLabs\ACL\Model\ResourceInterface;
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
     * @param SecurityIdentityInterface $identity
     * @param string                    $action
     * @param ResourceInterface         $resource
     *
     * @throws \RuntimeException The resource is not persisted (ID must be not null).
     * @return boolean Is allowed, or not.
     */
    public function isAllowed(SecurityIdentityInterface $identity, $action, ResourceInterface $resource)
    {
        if ($resource->getId() === null) {
            throw new \RuntimeException(sprintf(
                'The resource %s must be persisted (id not null) to be able to test the permissions',
                get_class($resource)
            ));
        }

        $repository = $this->entityManager->getRepository(get_class($resource));
        $qb = $repository->createQueryBuilder('resource');
        $qb->select('count(resource)')
            ->innerJoin('resource.authorizations', 'auth')
            ->where('resource = :resource')
            ->andWhere('auth.actions.' . $action . ' = true')
            ->andWhere('auth.securityIdentity = :securityIdentity');
        $qb->setParameter('resource', $resource);
        $qb->setParameter('securityIdentity', $identity);

        return ($qb->getQuery()->getSingleScalarResult() > 0);
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
