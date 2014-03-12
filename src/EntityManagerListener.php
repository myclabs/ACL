<?php

namespace MyCLabs\ACL;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use MyCLabs\ACL\Model\EntityResourceInterface;

/**
 * Listens the entity manager for new resources.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class EntityManagerListener implements EventSubscriber
{
    /**
     * @var ACLManager
     */
    private $aclManager;

    /**
     * @var EntityResourceInterface[]
     */
    private $newResources = [];

    public function __construct(ACLManager $aclManager)
    {
        $this->aclManager = $aclManager;
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
            if ($entity instanceof EntityResourceInterface) {
                $this->newResources[] = $entity;
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args)
    {
        foreach ($this->newResources as $resource) {
            $this->aclManager->processNewResource($resource);
        }

        $this->newResources = [];
    }
}
