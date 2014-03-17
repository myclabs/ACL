<?php

namespace MyCLabs\ACL\Model;

use Doctrine\ORM\EntityManager;

/**
 * Class field resource.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
final class ClassFieldResource implements ResourceInterface, CascadingResource
{
    /**
     * @var string
     */
    private $class;

    /**
     * @var string
     */
    private $field;

    /**
     * @param string $class Class name.
     * @param string $field Field name.
     */
    public function __construct($class, $field)
    {
        $this->class = $class;
        $this->field = $field;
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

    /**
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    public function getParentResources(EntityManager $entityManager)
    {
        return [];
    }

    public function getSubResources(EntityManager $entityManager)
    {
        $repository = $entityManager->getRepository($this->class);

        $resources = [];
        foreach ($repository->findAll() as $entity) {
            $resources[] = new EntityFieldResource($entity, $this->field);
        }

        return $resources;
    }
}
