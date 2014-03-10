<?php

namespace Tests\MyCLabs\ACL\Integration\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use MyCLabs\ACL\Model\Role;
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
     * @var Role[]|Collection
     * @ORM\OneToMany(targetEntity="MyCLabs\ACL\Model\Role", mappedBy="securityIdentity",
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
