<?php

namespace Tests\MyCLabs\ACL\Unit\Repository\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use MyCLabs\ACL\Model\RoleEntry;
use MyCLabs\ACL\Model\SecurityIdentityInterface;
use MyCLabs\ACL\Model\SecurityIdentityTrait;

/**
 * @ORM\Entity
 */
class User implements SecurityIdentityInterface
{
    use SecurityIdentityTrait;

    /**
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var RoleEntry[]|Collection
     * @ORM\OneToMany(targetEntity="MyCLabs\ACL\Model\RoleEntry", mappedBy="securityIdentity",
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
