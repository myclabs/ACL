<?php

namespace MyCLabs\ACL\Model;

/**
 * Class resource.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class ClassResource implements ResourceInterface, CascadingResource
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

    public function getParentResources()
    {
        return [];
    }

    public function getSubResources()
    {
        $repository = $entityManager->getRepository($this->class);

        return $repository->findAll();
    }

    public function getResourceId()
    {
        return new ResourceId(self::class, $this->class);
    }
}
