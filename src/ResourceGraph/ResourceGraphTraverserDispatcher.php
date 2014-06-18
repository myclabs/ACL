<?php

namespace MyCLabs\ACL\ResourceGraph;

use Doctrine\Common\Util\ClassUtils;
use MyCLabs\ACL\Model\ResourceInterface;

/**
 * This is a traverser that dispatches to other traversers based on the resource class.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class ResourceGraphTraverserDispatcher implements ResourceGraphTraverser
{
    /**
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
     * @param string                 $entityClass
     * @param ResourceGraphTraverser $resourceGraphTraverser
     */
    public function setResourceGraphTraverser($entityClass, ResourceGraphTraverser $resourceGraphTraverser)
    {
        $this->traversers[$entityClass] = $resourceGraphTraverser;
    }

    /**
     * @param object $resource
     * @return ResourceGraphTraverser|null
     */
    private function getResourceGraphTraverser($resource)
    {
        $entityClass = ClassUtils::getClass($resource);

        if (isset($this->traversers[$entityClass])) {
            return $this->traversers[$entityClass];
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
