<?php

namespace Tests\MyCLabs\ACL\Unit\Repository\Model;

use Doctrine\ORM\Mapping as ORM;
use MyCLabs\ACL\Model\EntityResource;

/**
 * @ORM\Entity
 */
class File implements EntityResource
{
    /**
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    public function getId()
    {
        return $this->id;
    }
}
