<?php

namespace MyCLabs\ACL\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Authorization of a security identity to do something on a resource.
 *
 * @ORM\Entity(readOnly=true)
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
abstract class Authorization
{
    /**
     * @var int
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * Role that created the authorization.
     *
     * @var Role
     * @ORM\ManyToOne(targetEntity="Role", inversedBy="authorizations")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected $role;

    /**
     * @var SecurityIdentityInterface
     * @ORM\ManyToOne(targetEntity="SecurityIdentityInterface")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected $securityIdentity;

    /**
     * @var Actions
     * @ORM\Embedded(class="Actions")
     */
    protected $actions;

    /**
     * The resource (entity) targeted by the authorization.
     * If null, then $resourceClass is used and this authorization is at class-scope.
     *
     * @var ResourceInterface|null
     */
    protected $resource;

    /**
     * Must be defined when $resource is null.
     * If defined, then the authorization applies to all the entities of that class.
     *
     * @ORM\Column(nullable=true)
     * @var string|null
     */
    protected $resourceClass;

    /**
     * @var Authorization
     * @ORM\ManyToOne(targetEntity="Authorization", inversedBy="childAuthorizations")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $parentAuthorization;

    /**
     * @var Authorization[]|Collection
     * @ORM\OneToMany(targetEntity="Authorization", mappedBy="parentAuthorization")
     */
    protected $childAuthorizations;

    /**
     * Creates an authorization on a entity resource.
     *
     * @param Role              $role
     * @param Actions           $actions
     * @param ResourceInterface $resource
     * @return static
     */
    public static function create(Role $role, Actions $actions, ResourceInterface $resource)
    {
        return new static($role, $actions, $resource);
    }

    /**
     * Creates an authorization on a resource class.
     *
     * @param Role    $role
     * @param Actions $actions
     * @param string  $resourceClass
     * @return static
     */
    public static function createOnResourceClass(Role $role, Actions $actions, $resourceClass)
    {
        return new static($role, $actions, null, $resourceClass);
    }

    /**
     * Creates an authorizations that inherits from another.
     *
     * @param Authorization     $parentAuthorization
     * @param ResourceInterface $resource
     * @param Actions|null      $actions
     * @return static
     */
    public static function createChildAuthorization(
        Authorization $parentAuthorization,
        ResourceInterface $resource,
        Actions $actions = null
    ) {
        $actions = $actions ?: $parentAuthorization->getActions();

        $authorization = self::create($parentAuthorization->role, $actions, $resource);

        $authorization->parentAuthorization = $parentAuthorization;

        return $authorization;
    }

    /**
     * @param Role                   $role
     * @param Actions                $actions
     * @param ResourceInterface|null $resource
     * @param string                 $resourceClass
     */
    private function __construct(
        Role $role,
        Actions $actions,
        ResourceInterface $resource = null,
        $resourceClass = null
    ) {
        $this->role = $role;
        $this->securityIdentity = $role->getSecurityIdentity();
        $this->actions = $actions;
        $this->resource = $resource;
        if ($resource === null) {
            $this->resourceClass = $resourceClass;
        }

        $this->childAuthorizations = new ArrayCollection();

        // Add to the role because the role might need its root authorizations
        // for cascading (in case new resources are created later in the same thread)
        $role->addAuthorization($this);
    }

    /**
     * @return SecurityIdentityInterface
     */
    public function getSecurityIdentity()
    {
        return $this->securityIdentity;
    }

    /**
     * @return Actions
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @return ResourceInterface|null
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @return string|null
     */
    public function getResourceClass()
    {
        return $this->resourceClass;
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
     * @return Role
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
