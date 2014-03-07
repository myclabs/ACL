<?php

namespace Tests\MyCLabs\ACL\Integration;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use MyCLabs\ACL\Model\Role;
use MyCLabs\ACL\Model\SecurityIdentityInterface;
use MyCLabs\ACL\Model\SecurityIdentityTrait;

/**
 * @Entity
 */
class User implements SecurityIdentityInterface
{
    use SecurityIdentityTrait;

    /**
     * @Id @GeneratedValue
     * @Column(type="integer")
     */
    protected $id;

    /**
     * @var Role[]|Collection
     * @OneToMany(targetEntity="MyCLabs\ACL\Model\Role", mappedBy="user", cascade={"persist", "remove"})
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
