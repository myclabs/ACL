<?php

namespace MyCLabs\ACL\ResourceGraph;

use Doctrine\ORM\EntityManager;
use MyCLabs\ACL\Model\CascadingResource;
use MyCLabs\ACL\Model\ResourceInterface;

/**
 * Traverser for resources implementing CascadingResource.
 *
 * CascadingResource don't return all sub-resources (only the direct ones), so we need to do
 * the traversal recursively in this class.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class CascadingResourceGraphTraverser implements ResourceGraphTraverser
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ResourceGraphTraverser
     */
    private $parentTraverser;

    /**
     * @param EntityManager          $entityManager
     * @param ResourceGraphTraverser $parentTraverser We need the parent traverser to use it
     *        to recursively traverse resources. This is because CascadingResource returns
     *        returns only its direct parent and sub-resources.
     */
    public function __construct(EntityManager $entityManager, ResourceGraphTraverser $parentTraverser)
    {
        $this->entityManager = $entityManager;
        $this->parentTraverser = $parentTraverser;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllParentResources(ResourceInterface $resource)
    {
        if (! $resource instanceof CascadingResource) {
            return [];
        }

        $parentResources = [];

        foreach ($resource->getParentResources($this->entityManager) as $parentResource) {
            $parentResources[] = $parentResource;

            // Recursively get its sub-resources
            $parentResources = array_merge(
                $parentResources,
                $this->parentTraverser->getAllParentResources($parentResource)
            );
        }

        return $this->unique($parentResources);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllSubResources(ResourceInterface $resource)
    {
        if (! $resource instanceof CascadingResource) {
            return [];
        }

        $subResources = [];

        foreach ($resource->getSubResources($this->entityManager) as $subResource) {
            $subResources[] = $subResource;

            // Recursively get its sub-resources
            $subResources = array_merge(
                $subResources,
                $this->parentTraverser->getAllSubResources($subResource)
            );
        }

        return $this->unique($subResources);
    }

    /**
     * Array unique but with objects.
     *
     * @param array $array
     *
     * @return array
     */
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
