<?php

namespace MyCLabs\ACL\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * ID of an ACL resource.
 *
 * @ORM\Embeddable
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class ResourceId
{
    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $name;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
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

    public function __toString()
    {
        if ($this->id === null) {
            return $this->name;
        }

        return $this->name . '(' . $this->id . ')';
    }
}
