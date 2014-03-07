<?php

namespace MyCLabs\ACL\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Authorization of a security identity to do something on a resource.
 *
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="type", type="string")
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
abstract class Authorization
{
    /**
     * @var int
     * @Id @GeneratedValue
     * @Column(type="integer")
     */
    protected $id;

    /**
     * Role that created the authorization.
     *
     * @var Role
     * @ManyToOne(targetEntity="Role", inversedBy="authorizations")
     * @JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected $role;

    /**
     * @var SecurityIdentityInterface
     * @ManyToOne(targetEntity="SecurityIdentityInterface", inversedBy="authorizations")
     * @JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected $securityIdentity;

    /**
     * @var Actions
     * @Embedded(class="Actions")
     */
    protected $actions;

    /**
     * @var ResourceInterface
     */
    protected $resource;

    /**
     * Héritage des droits entre ressources.
     *
     * @var Authorization
     * @ManyToOne(targetEntity="Authorization", inversedBy="childAuthorizations")
     * @JoinColumn(onDelete="CASCADE")
     */
    protected $parentAuthorization;

    /**
     * @var Authorization[]|Collection
     * @OneToMany(targetEntity="Authorization", mappedBy="parentAuthorization")
     */
    protected $childAuthorizations;

    /**
     * @param Role              $role
     * @param Actions           $actions
     * @param ResourceInterface $resource
     * @return static
     */
    public static function create(Role $role, Actions $actions, ResourceInterface $resource)
    {
        $authorization = new static($role, $role->getSecurityIdentity(), $actions, $resource);

        $resource->addAuthorization($authorization);

        return $authorization;
    }

    /**
     * Crée une autorisation qui hérite d'une autre.
     *
     * @param Authorization     $parentAuthorization
     * @param ResourceInterface $resource Nouvelle ressource
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
     * @param Role                      $role
     * @param SecurityIdentityInterface $identity
     * @param Actions                   $actions
     * @param ResourceInterface         $resource
     */
    private function __construct(
        Role $role,
        SecurityIdentityInterface $identity,
        Actions $actions,
        ResourceInterface $resource
    ) {
        $this->role = $role;
        $this->securityIdentity = $identity;
        $this->actions = $actions;
        $this->resource = $resource;

        $this->childAuthorizations = new ArrayCollection();
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
     * @return ResourceInterface
     */
    public function getResource()
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
     * @param Authorization $parentAuthorization
     */
    public function setParentAuthorization(Authorization $parentAuthorization)
    {
        $this->parentAuthorization = $parentAuthorization;
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
