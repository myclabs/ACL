<?php

namespace MyCLabs\ACL;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use MyCLabs\ACL\Model\EntityResource;

/**
 * Listens the entity manager for new resources.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class EntityManagerListener implements EventSubscriber
{
    /**
     * @var callable
     */
    private $aclManagerLocator;

    /**
     * @var ACLManager|null
     */
    private $aclManager;

    /**
     * @var EntityResource[]
     */
    private $newResources = [];

    /**
     * Because of a circular dependency, we can't require to inject directly the ACL manager.
     * You need to inject a "locator", i.e. a callable that returns the ACL manager,
     * so that the ACL manager can be fetched lazily.
     *
     * @param callable $aclManagerLocator Callable that returns the ACL manager.
     */
    public function __construct(callable $aclManagerLocator)
    {
        $this->aclManagerLocator = $aclManagerLocator;
    }

    public function getSubscribedEvents()
    {
        return [
            Events::onFlush,
            Events::postFlush,
        ];
    }

    public function onFlush(OnFlushEventArgs $args)
    {
        $uow = $args->getEntityManager()->getUnitOfWork();

        // Remember new resources
        $this->newResources = [];
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof EntityResource) {
                $this->newResources[] = $entity;
            }
        }
    }

    public function postFlush()
    {
        $aclManager = $this->getACLManager();

        foreach ($this->newResources as $resource) {
            $aclManager->processNewResource($resource);
        }

        $this->newResources = [];
    }

    private function getACLManager()
    {
        if ($this->aclManager === null) {
            // Resolve the ACL manager
            $locator = $this->aclManagerLocator;
            $this->aclManager = $locator();
        }

        return $this->aclManager;
    }
}
