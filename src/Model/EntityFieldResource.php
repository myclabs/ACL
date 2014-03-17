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
     * @var EntityResource
     */
    private $entity;

    /**
     * @var string
     */
    private $field;

    /**
     * @param EntityResource $entity
     * @param string         $field Field name.
     */
    public function __construct(EntityResource $entity, $field)
    {
        $this->entity = $entity;
        $this->field = $field;
    }

    /**
     * @return EntityResource
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
