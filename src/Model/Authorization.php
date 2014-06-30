<?php

namespace MyCLabs\ACL\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Authorization of an identity to do something on a resource.
 *
 * @ORM\Entity(readOnly=true, repositoryClass="MyCLabs\ACL\Repository\AuthorizationRepository")
 * @ORM\Table(name="ACL_Authorization", indexes={
 *     @ORM\Index(name="is_allowed", columns={"resource_id", "resource_name", "identity_id"}),
 *     @ORM\Index(name="root_authorizations", columns={"parentAuthorization_id", "resource_id", "resource_name"})
 * })
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class Authorization
{
    /**
     * @var int
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * RoleEntry that created the authorization.
     *
     * @var RoleEntry
     * @ORM\ManyToOne(targetEntity="RoleEntry", inversedBy="authorizations")
     * @ORM\JoinColumn(name="role_entry_id", nullable=false, onDelete="CASCADE")
     */
    protected $roleEntry;

    /**
     * @var Identity
     * @ORM\ManyToOne(targetEntity="Identity")
     * @ORM\JoinColumn(name="identity_id", nullable=false, onDelete="CASCADE")
     */
    protected $identity;

    /**
     * @var Actions
     * @ORM\Embedded(class="Actions")
     */
    protected $actions;

    /**
     * @var ResourceId
     * @ORM\Embedded(class="ResourceId")
     */
    protected $resource;

    /**
     * @var Authorization
     * @ORM\ManyToOne(targetEntity="Authorization", inversedBy="childAuthorizations")
     * @ORM\JoinColumn(name="parentAuthorization_id", onDelete="CASCADE")
     */
    protected $parentAuthorization;

    /**
     * @var Authorization[]|Collection
     * @ORM\OneToMany(targetEntity="Authorization", mappedBy="parentAuthorization")
     */
    protected $childAuthorizations;

    /**
     * @var boolean
     * @ORM\Column(name="cascadable", type="boolean")
     */
    protected $cascadable;

    /**
     * Creates an authorization on a resource.
     *
     * @param RoleEntry         $roleEntry
     * @param Actions           $actions
     * @param ResourceInterface $resource
     * @param bool              $cascade Should this authorization cascade?
     * @throws \RuntimeException
     * @return static
     */
    public static function create(RoleEntry $roleEntry, Actions $actions, ResourceInterface $resource, $cascade = true)
    {
        return new static($roleEntry, $actions, $resource->getResourceId(), $cascade);
    }

    /**
     * @param RoleEntry  $roleEntry
     * @param Actions    $actions
     * @param ResourceId $resourceId
     * @param bool       $cascade Should this authorization cascade?
     */
    private function __construct(
        RoleEntry $roleEntry,
        Actions $actions,
        ResourceId $resourceId,
        $cascade
    ) {
        $this->roleEntry = $roleEntry;
        $this->identity = $roleEntry->getIdentity();
        $this->actions = $actions;
        $this->resource = $resourceId;
        $this->cascadable = $cascade;

        $this->childAuthorizations = new ArrayCollection();
    }

    /**
     * Cascade an authorization to another resource (will return a child authorization).
     *
     * @param ResourceInterface $resource
     * @return static
     */
    public function createChildAuthorization(ResourceInterface $resource)
    {
        $authorization = self::create($this->roleEntry, $this->actions, $resource);

        $authorization->parentAuthorization = $this;

        return $authorization;
    }

    /**
     * @return Identity
     */
    public function getIdentity()
    {
        return $this->identity;
    }

    /**
     * @return Actions
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @return ResourceId
     */
    public function getResourceId()
    {
        return $this->resource;
    }

    /**
     * @return Authorization
     */
    public function getParentAuthorization()
    {
        return $this->parentAuthorization;
    }

    /**
     * @return static[]
     */
    public function getChildAuthorizations()
    {
        return $this->childAuthorizations;
    }

    /**
     * @return bool
     */
    public function isRoot()
    {
        return ($this->parentAuthorization === null);
    }

    /**
     * @return RoleEntry
     */
    public function getRoleEntry()
    {
        return $this->roleEntry;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return boolean
     */
    public function isCascadable()
    {
        return $this->cascadable;
    }
}
