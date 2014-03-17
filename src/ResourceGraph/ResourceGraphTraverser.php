<?php

namespace MyCLabs\ACL\ResourceGraph;

use MyCLabs\ACL\Model\ResourceInterface;

/**
 * Traverses a resource graph.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface ResourceGraphTraverser
{
    /**
     * Returns all the parent resources of the given resource recursively.
     *
     * @param ResourceInterface $resource
     *
     * @return ResourceInterface[]
     */
    public function getAllParentResources(ResourceInterface $resource);

    /**
     * Returns all the sub-resources of the given resource recursively.
     *
     * @param ResourceInterface $resource
     *
     * @return ResourceInterface[]
     */
    public function getAllSubResources(ResourceInterface $resource);
}
