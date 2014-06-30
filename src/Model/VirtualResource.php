<?php

namespace MyCLabs\ACL\Model;

/**
 * Resource that represents an arbitrary concept, hence the name "virtual".
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class VirtualResource implements ResourceInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var int|null
     */
    private $id;

    /**
     * @param string   $name
     * @param int|null $id
     */
    public function __construct($name, $id = null)
    {
        $this->name = (string) $name;
        $this->id = $id;
    }

    public function getResourceId()
    {
        return new ResourceId($this->name, $this->id);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }
}
