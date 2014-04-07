<?php

namespace MyCLabs\ACL\Doctrine;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use MyCLabs\ACL\ACL;
use MyCLabs\ACL\Model\EntityResource;

/**
 * Listens the entity manager for new resources.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class EntityResourcesListener implements EventSubscriber
{
    /**
     * @var callable
     */
    private $aclLocator;

    /**
     * @var ACL|null
     */
    private $acl;

    /**
     * @var EntityResource[]
     */
    private $newResources = [];

    /**
     * Because of a circular dependency, we can't require to inject directly the ACL.
     * You need to inject a "locator", i.e. a callable that returns the ACL,
     * so that the ACL can be fetched lazily.
     *
     * @param callable $aclLocator Callable that returns the ACL.
     */
    public function __construct(callable $aclLocator)
    {
        $this->aclLocator = $aclLocator;
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
        $acl = $this->getACL();

        foreach ($this->newResources as $resource) {
            $acl->processNewResource($resource);
        }

        $this->newResources = [];
    }

    private function getACL()
    {
        if ($this->acl === null) {
            // Resolve the ACL
            $locator = $this->aclLocator;
            $this->acl = $locator();
        }

        return $this->acl;
    }
}
