<?php

namespace MyCLabs\ACL\CascadeStrategy;

use Doctrine\ORM\EntityManager;
use MyCLabs\ACL\Model\Authorization;
use MyCLabs\ACL\Model\CascadingResource;
use MyCLabs\ACL\Model\ResourceInterface;
use MyCLabs\ACL\Repository\AuthorizationRepository;

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
        $authorizations = [];

        if ($resource instanceof CascadingResource) {
            foreach ($this->getAllSubResources($resource) as $subResource) {
                $authorizations[] = $authorization->createChildAuthorization($subResource);
            }
        }

        return $authorizations;
    }

    /**
     * {@inheritdoc}
     */
    public function processNewResource(ResourceInterface $resource)
    {
        if (! $resource instanceof CascadingResource) {
            return [];
        }

        // Find non cascaded authorizations on the parent resources
        $authorizationsToCascade = [];
        foreach ($this->getAllParentResources($resource) as $parentResource) {
            $authorizationsToCascade = array_merge(
                $authorizationsToCascade,
                $this->authorizationRepository->findNonCascadedAuthorizationsForResource($parentResource)
            );
        }

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
}
