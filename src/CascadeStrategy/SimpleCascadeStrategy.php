<?php

namespace MyCLabs\ACL\CascadeStrategy;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use MyCLabs\ACL\Model\Authorization;
use MyCLabs\ACL\Model\CascadingResource;
use MyCLabs\ACL\Model\ResourceInterface;
use MyCLabs\ACL\Repository\AuthorizationRepository;
use MyCLabs\ACL\ResourceGraph\ResourceGraphTraverser;

/**
 * Simple cascade: authorizations are cascaded from a resource to its sub-resources.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class SimpleCascadeStrategy implements CascadeStrategy
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var AuthorizationRepository
     */
    private $authorizationRepository;

    /**
     * @var ResourceGraphTraverser[]
     */
    private $resourceGraphTraversers = [];

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->authorizationRepository = $entityManager->getRepository('MyCLabs\ACL\Model\Authorization');
    }

    /**
     * {@inheritdoc}
     */
    public function cascadeAuthorization(Authorization $authorization, ResourceInterface $resource)
    {
        // Find sub-resources
        $subResources = [];
        if ($resource instanceof CascadingResource) {
            $subResources = $this->getAllSubResources($resource);
        } else {
            $traverser = $this->getResourceGraphTraverser(ClassUtils::getClass($resource));

            if ($traverser) {
                $subResources = $traverser->getAllSubResources($resource);
            }
        }

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
        // Find parent resources
        $parentResources = [];
        if ($resource instanceof CascadingResource) {
            $parentResources = $this->getAllParentResources($resource);
        } else {
            $traverser = $this->getResourceGraphTraverser(ClassUtils::getClass($resource));

            if ($traverser) {
                $parentResources = $traverser->getAllParentResources($resource);
            }
        }

        // Find root authorizations on the parent resources
        $authorizationsToCascade = [];
        foreach ($parentResources as $parentResource) {
            $authorizationsToCascade = array_merge(
                $authorizationsToCascade,
                $this->authorizationRepository->findNonCascadedAuthorizationsForResource($parentResource)
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
     * Get all parent resources recursively.
     * @param CascadingResource $resource
     * @return ResourceInterface[]
     */
    private function getAllParentResources(CascadingResource $resource)
    {
        $parents = [];

        foreach ($resource->getParentResources($this->entityManager) as $parentResource) {
            $parents[] = $parentResource;
            if ($parentResource instanceof CascadingResource) {
                $parents = array_merge($parents, $this->getAllParentResources($parentResource));
            }
        }

        return $this->unique($parents);
    }

    /**
     * Get all sub-resources recursively.
     * @param CascadingResource $resource
     * @return ResourceInterface[]
     */
    private function getAllSubResources(CascadingResource $resource)
    {
        $subResources = [];

        foreach ($resource->getSubResources($this->entityManager) as $subResource) {
            $subResources[] = $subResource;
            if ($subResource instanceof CascadingResource) {
                $subResources = array_merge($subResources, $this->getAllSubResources($subResource));
            }
        }

        return $this->unique($subResources);
    }

    private function unique(array $array)
    {
        $result  = [];

        foreach ($array as $item) {
            if (! in_array($item, $result, true)) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * @param string                 $entityClass
     * @param ResourceGraphTraverser $resourceGraphTraverser
     */
    public function setResourceGraphTraverser($entityClass, $resourceGraphTraverser)
    {
        $this->resourceGraphTraversers[$entityClass] = $resourceGraphTraverser;
    }

    /**
     * @param string $entityClass
     * @return ResourceGraphTraverser|null
     */
    private function getResourceGraphTraverser($entityClass)
    {
        if (isset($this->resourceGraphTraversers[$entityClass])) {
            return $this->resourceGraphTraversers[$entityClass];
        }

        return null;
    }
}
