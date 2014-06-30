<?php

namespace MyCLabs\ACL\ResourceGraph;

use MyCLabs\ACL\Model\ResourceInterface;

/**
 * This is a traverser that dispatches to other traversers based on the resource name.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class ResourceGraphTraverserDispatcher implements ResourceGraphTraverser
{
    /**
     * Resource graph traversers indexed by the resource name.
     *
     * @var ResourceGraphTraverser[]
     */
    private $traversers = [];

    /**
     * {@inheritdoc}
     */
    public function getAllParentResources(ResourceInterface $resource)
    {
        $traverser = $this->getResourceGraphTraverser($resource);
        if (!$traverser) {
            return [];
        }

        return $traverser->getAllParentResources($resource);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllSubResources(ResourceInterface $resource)
    {
        $traverser = $this->getResourceGraphTraverser($resource);
        if (!$traverser) {
            return [];
        }

        return $traverser->getAllSubResources($resource);
    }

    /**
     * @param string                 $resourceName
     * @param ResourceGraphTraverser $traverser
     */
    public function setResourceGraphTraverser($resourceName, ResourceGraphTraverser $traverser)
    {
        $this->traversers[$resourceName] = $traverser;
    }

    /**
     * @param ResourceInterface $resource
     * @return ResourceGraphTraverser|null
     */
    private function getResourceGraphTraverser(ResourceInterface $resource)
    {
        $name = $resource->getResourceId()->getName();

        if (isset($this->traversers[$name])) {
            return $this->traversers[$name];
        }

        // We also try using instanceof so that we cover inheritance and interfaces
        foreach ($this->traversers as $class => $traverser) {
            if ($resource instanceof $class) {
                return $traverser;
            }
        }

        return null;
    }
}
