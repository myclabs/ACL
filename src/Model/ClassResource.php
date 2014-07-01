<?php

namespace MyCLabs\ACL\Model;

use Doctrine\ORM\EntityManager;

/**
 * Class resource.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
final class ClassResource implements ResourceInterface, CascadingResource
{
    /**
     * @var string
     */
    private $class;

    /**
     * @param string $class Class name.
     */
    public function __construct($class)
    {
        $this->class = $class;
    }

    /**
     * Returns the name of the class.
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    public function getParentResources(EntityManager $entityManager)
    {
        return [];
    }

    public function getSubResources(EntityManager $entityManager)
    {
        $repository = $entityManager->getRepository($this->class);

        return $repository->findAll();
    }

    public function getResourceId()
    {
        return new ResourceId($this->class);
    }
}
