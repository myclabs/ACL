<?php

namespace MyCLabs\ACL\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use MyCLabs\ACL\ACL;

/**
 * Role.
 *
 * @ORM\Entity
 * @ORM\Table(name="ACL_Role")
 *
 */
class Role
{
    /**
     * @var int
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var SecurityIdentityInterface
     * @ORM\ManyToOne(targetEntity="SecurityIdentityInterface", inversedBy="roles")
     */
    protected $securityIdentity;

    /**
     * @var Authorization[]|Collection
     * @ORM\OneToMany(targetEntity="Authorization", mappedBy="role", fetch="EXTRA_LAZY")
     */
    protected $authorizations;

    /**
     * @ORM\Column(type="integer", nullable=true)
     **/
    protected $resourceId;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $name;

    public function __construct(SecurityIdentityInterface $identity, $name, ResourceInterface $resource)
    {
        $this->authorizations = new ArrayCollection();
        $this->securityIdentity = $identity;
        if ($resource instanceof EntityResource) {
            $this->resourceId = $resource->getId();
        }
        $this->name = $name;
    }

    /**
     * @param ACL $acl
     * @param $actions
     * @param $resource
     * @return Authorization[]
     */
    public function createAuthorizations(ACL $acl, $actions, $resource)
    {
        $acl->allow(
            $this,
            $actions,
            $resource
        );
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return SecurityIdentityInterface
     */
    public function getSecurityIdentity()
    {
        return $this->securityIdentity;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getResourceId()
    {
        return $this->resourceId;
    }
}
