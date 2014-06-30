<?php

namespace Tests\MyCLabs\ACL\Unit\Repository\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use MyCLabs\ACL\Model\Identity;
use MyCLabs\ACL\Model\IdentityTrait;
use MyCLabs\ACL\Model\RoleEntry;

/**
 * @ORM\Entity
 */
class User implements Identity
{
    use IdentityTrait;

    /**
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var RoleEntry[]|Collection
     * @ORM\OneToMany(targetEntity="MyCLabs\ACL\Model\RoleEntry", mappedBy="identity",
     * cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $roles;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }
}
