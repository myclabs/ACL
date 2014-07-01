<?php

namespace MyCLabs\ACL\CascadeStrategy;

use MyCLabs\ACL\Adapter\BackendAdapter;
use MyCLabs\ACL\Model\Authorization;
use MyCLabs\ACL\Model\CascadingResource;
use MyCLabs\ACL\Model\ResourceInterface;
use MyCLabs\ACL\ResourceGraph\CascadingResourceGraphTraverser;
use MyCLabs\ACL\ResourceGraph\ResourceGraphTraverser;
use MyCLabs\ACL\ResourceGraph\ResourceGraphTraverserDispatcher;

/**
 * Simple cascade: authorizations are cascaded from a resource to its sub-resources.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class SimpleCascadeStrategy implements CascadeStrategy
{
    /**
     * @var BackendAdapter
     */
    private $adapter;

    /**
     * @var ResourceGraphTraverserDispatcher
     */
    private $resourceGraphTraverser;

    public function __construct(BackendAdapter $adapter)
    {
        $this->adapter = $adapter;

        $this->resourceGraphTraverser = new ResourceGraphTraverserDispatcher();
        // Default traverser for CascadingResource
        $this->resourceGraphTraverser->setResourceGraphTraverser(
            CascadingResource::class,
            new CascadingResourceGraphTraverser($this->resourceGraphTraverser)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function cascadeAuthorization(Authorization $authorization, ResourceInterface $resource)
    {
        $subResources = $this->resourceGraphTraverser->getAllSubResources($resource);

        // Cascade authorizations
        $authorizations = [];
        foreach ($subResources as $subResource) {
            $authorizations[] = $authorization->createChildAuthorization($subResource);
        }

        return $authorizations;
    }

    /**
     * {@inheritdoc}
     */
    public function processNewResource(ResourceInterface $resource)
    {
        $repository = $this->adapter->getAuthorizationRepository();

        $parentResources = $this->resourceGraphTraverser->getAllParentResources($resource);

        // Find root authorizations on the parent resources
        $authorizationsToCascade = [];
        foreach ($parentResources as $parentResource) {
            $authorizationsToCascade = array_merge(
                $authorizationsToCascade,
                $repository->findCascadableAuthorizationsForResource($parentResource)
            );
        }

        // Cascade them
        $authorizations = [];
        foreach ($authorizationsToCascade as $authorizationToCascade) {
            /** @var Authorization $authorizationToCascade */
            $authorizations[] = $authorizationToCascade->createChildAuthorization($resource);
        }

        return $authorizations;
    }

    /**
     * @param string                 $entityClass
     * @param ResourceGraphTraverser $resourceGraphTraverser
     */
    public function setResourceGraphTraverser($entityClass, $resourceGraphTraverser)
    {
        $this->resourceGraphTraverser->setResourceGraphTraverser($entityClass, $resourceGraphTraverser);
    }
}
