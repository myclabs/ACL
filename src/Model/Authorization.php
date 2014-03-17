<?php

namespace MyCLabs\ACL\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping as ORM;

/**
 * Authorization of a security identity to do something on a resource.
 *
 * @ORM\Entity(readOnly=true, repositoryClass="MyCLabs\ACL\Repository\AuthorizationRepository")
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
     * Role that created the authorization.
     *
     * @var Role
     * @ORM\ManyToOne(targetEntity="Role", inversedBy="authorizations")
     * @ORM\JoinColumn(name="role_id", nullable=false, onDelete="CASCADE")
     */
    protected $role;

    /**
     * @var SecurityIdentityInterface
     * @ORM\ManyToOne(targetEntity="SecurityIdentityInterface")
     * @ORM\JoinColumn(name="securityIdentity_id", nullable=false, onDelete="CASCADE")
     */
    protected $securityIdentity;

    /**
     * @var Actions
     * @ORM\Embedded(class="Actions")
     */
    protected $actions;

    /**
     * The entity targeted by the authorization.
     * If null, then $entityClass is used and this authorization is at class-scope.
     *
     * @ORM\Column(name="entity_id", type="integer", nullable=true)
     * @var int|null
     */
    protected $entityId;

    /**
     * The class of the entity.
     *
     * @ORM\Column(name="entity_class")
     * @var string
     */
    protected $entityClass;

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
     * Creates an authorization on a resource.
     *
     * @param Role              $role
     * @param Actions           $actions
     * @param ResourceInterface $resource
     * @throws \RuntimeException
     * @return static
     */
    public static function create(Role $role, Actions $actions, ResourceInterface $resource)
    {
        if ($resource instanceof EntityResource) {
            return new static($role, $actions, $resource);
        } elseif ($resource instanceof ClassResource) {
            return new static($role, $actions, null, $resource->getClass());
        }

        throw new \RuntimeException('Unknown type of resource: ' . get_class($resource));
    }

    /**
     * @param Role                $role
     * @param Actions             $actions
     * @param EntityResource|null $entity
     * @param string|null         $entityClass
     */
    private function __construct(
        Role $role,
        Actions $actions,
        EntityResource $entity = null,
        $entityClass = null
    ) {
        $this->role = $role;
        $this->securityIdentity = $role->getSecurityIdentity();
        $this->actions = $actions;
        if ($entity !== null) {
            $this->entityId = $entity->getId();
        }
        if ($entity === null) {
            $this->entityClass = $entityClass;
        } else {
            $this->entityClass = ClassUtils::getClass($entity);
        }

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
        $authorization = self::create($this->role, $this->actions, $resource);

        $authorization->parentAuthorization = $this;

        return $authorization;
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
     * @return int|null
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * @return string|null
     */
    public function getEntityClass()
    {
        return $this->entityClass;
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
}
