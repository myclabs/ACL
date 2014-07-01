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
     * @param string   $name
     */
    public function __construct($name)
    {
        $this->name = (string) $name;
    }

    public function getResourceId()
    {
        return new ResourceId(self::class, $this->name);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
