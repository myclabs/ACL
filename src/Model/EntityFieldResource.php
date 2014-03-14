<?php

namespace MyCLabs\ACL\Model;

/**
 * Entity field resource.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
final class EntityFieldResource implements ResourceInterface
{
    /**
     * @var EntityResourceInterface
     */
    private $entity;

    /**
     * @var string
     */
    private $field;

    /**
     * @param EntityResourceInterface $entity
     * @param string         $field Field name.
     */
    public function __construct(EntityResourceInterface $entity, $field)
    {
        $this->entity = $entity;
        $this->field = $field;
    }

    /**
     * @return EntityResourceInterface
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }
}
