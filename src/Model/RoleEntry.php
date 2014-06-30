<?php

namespace MyCLabs\ACL\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Instance of a role for a user.
 *
 * For example, given a role "Article Editor", this class represents "User X is Article Editor for Article Y"
 *
 * @ORM\Entity(readOnly=true, repositoryClass="MyCLabs\ACL\Repository\RoleEntryRepository")
 * @ORM\Table(name="ACL_RoleEntry")
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class RoleEntry
{
    /**
     * @var int
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(type="string", name="role_name")
     */
    protected $roleName;

    /**
     * @var SecurityIdentityInterface
     * @ORM\ManyToOne(targetEntity="SecurityIdentityInterface", inversedBy="roleEntries")
     */
    protected $securityIdentity;

    /**
     * @var Authorization[]|Collection
     * @ORM\OneToMany(targetEntity="Authorization", mappedBy="roleEntry", fetch="EXTRA_LAZY")
     */
    protected $authorizations;

    /**
     * @var ResourceId
     * @ORM\Embedded(class="ResourceId")
     */
    protected $resource;

    public function __construct(SecurityIdentityInterface $identity, $name, ResourceInterface $resource)
    {
        $this->roleName = $name;
        $this->authorizations = new ArrayCollection();
        $this->securityIdentity = $identity;
        $this->resource = $resource->getResourceId();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getRoleName()
    {
        return $this->roleName;
    }

    /**
     * @return SecurityIdentityInterface
     */
    public function getSecurityIdentity()
    {
        return $this->securityIdentity;
    }

    /**
     * @return ResourceId
     */
    public function getResourceId()
    {
        return $this->resource;
    }
}
