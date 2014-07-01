<?php

namespace MyCLabs\ACL\Adapter;

use Doctrine\ORM\EntityManager;
use MyCLabs\ACL\Model\Authorization;
use MyCLabs\ACL\Model\RoleEntry;

/**
 * Adapter for Doctrine.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class DoctrineAdapter implements BackendAdapter
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getAuthorizationRepository()
    {
        static $repository;

        if ($repository === null) {
            $repository = $this->entityManager->getRepository(Authorization::class);
        }

        return $repository;
    }

    public function getRoleEntryRepository()
    {
        static $repository;

        if ($repository === null) {
            $repository = $this->entityManager->getRepository(RoleEntry::class);
        }

        return $repository;
    }
}
