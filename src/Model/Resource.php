<?php

namespace MyCLabs\ACL\Model;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping as ORM;

/**
 * ACL resource.
 *
 * The resource can be one of these:
 *
 *     - entity class (i.e. all entities of the class)
 *     - entity
 *     - field of an entity class
 *     - field of an entity
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class Resource
{
    /**
     * @var string|null
     */
    private $entityClass;

    /**
     * @var EntityResourceInterface|null
     */
    private $entity;

    /**
     * @var string|null
     */
    private $field;

    /**
     * Returns the resource representing the given entity.
     * @param EntityResourceInterface $entity
     * @throws \RuntimeException The entity is not persisted (ID must be not null).
     * @return \MyCLabs\ACL\Model\Resource
     */
    public static function fromEntity(EntityResourceInterface $entity)
    {
        if ($entity->getId() === null) {
            throw new \RuntimeException(sprintf(
                'The entity resource %s must be persisted (id not null) to be able to test the permissions',
                ClassUtils::getClass($entity)
            ));
        }

        return new static($entity);
    }

    /**
     * Returns the resource representing all entities of the given class.
     * @param string $entityClass
     * @return \MyCLabs\ACL\Model\Resource
     */
    public static function fromEntityClass($entityClass)
    {
        return new static(null, $entityClass);
    }

    /**
     * Returns the resource representing the field of the entity.
     * @param EntityResourceInterface $entity
     * @param string                  $field
     * @throws \RuntimeException The entity is not persisted (ID must be not null).
     * @return \MyCLabs\ACL\Model\Resource
     */
    public static function fromEntityField(EntityResourceInterface $entity, $field)
    {
        if ($entity->getId() === null) {
            throw new \RuntimeException(sprintf(
                'The entity resource %s must be persisted (id not null) to be able to test the permissions',
                ClassUtils::getClass($entity)
            ));
        }

        return new static($entity, null, $field);
    }

    /**
     * Returns the resource representing the field of all entities of the class.
     * @param string $entityClass
     * @param string $field
     * @return \MyCLabs\ACL\Model\Resource
     */
    public static function fromEntityClassField($entityClass, $field)
    {
        return new static(null, $entityClass, $field);
    }

    private function __construct(EntityResourceInterface $entity = null, $entityClass = null, $field = null)
    {
        $this->entity = $entity;
        $this->entityClass = $entityClass;
        $this->field = $field;
    }

    public function isEntity()
    {
        return $this->entity !== null && $this->field === null;
    }

    public function isEntityClass()
    {
        return $this->entityClass !== null && $this->field === null;
    }

    public function isEntityField()
    {
        return $this->entity !== null && $this->field !== null;
    }

    public function isEntityClassField()
    {
        return $this->entityClass !== null && $this->field !== null;
    }

    /**
     * @return string|null
     */
    public function getEntityClass()
    {
        return $this->entityClass;
    }

    /**
     * @return EntityResourceInterface|null
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return string|null
     */
    public function getEntityField()
    {
        return $this->field;
    }
}
