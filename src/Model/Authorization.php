<?php

namespace MyCLabs\ACL\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping as ORM;

/**
 * Authorization of a security identity to do something on a resource.
 *
 * @ORM\Entity(readOnly=true)
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
     * Field name for authorizations that apply on a class or entity field.
     *
     * @ORM\Column(name="entity_field", nullable=true)
     * @var string|null
     */
    protected $entityField;

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
     * @param Role                        $role
     * @param Actions                     $actions
     * @param \MyCLabs\ACL\Model\Resource $resource
     * @throws \LogicException
     * @return static
     */
    public static function create(Role $role, Actions $actions, Resource $resource)
    {
        if ($resource->isEntity()) {
            return new static($role, $actions, $resource->getEntity());
        } elseif ($resource->isEntityClass()) {
            return new static($role, $actions, null, $resource->getEntityClass());
        } elseif ($resource->isEntityField()) {
            return new static($role, $actions, $resource->getEntity(), null, $resource->getEntityField());
        } elseif ($resource->isEntityClassField()) {
            return new static($role, $actions, null, $resource->getEntityClass(), $resource->getEntityField());
        }

        throw new \LogicException();
    }

    /**
     * @param Role                         $role
     * @param Actions                      $actions
     * @param EntityResourceInterface|null $entity
     * @param string|null                  $entityClass
     * @param string|null                  $entityField
     */
    private function __construct(
        Role $role,
        Actions $actions,
        EntityResourceInterface $entity = null,
        $entityClass = null,
        $entityField = null
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
        $this->entityField = $entityField;

        $this->childAuthorizations = new ArrayCollection();
    }

    /**
     * Cascade an authorization to another resource (will return a child authorization).
     *
     * @param \MyCLabs\ACL\Model\Resource $resource
     * @param Actions|null                $actions If not specified, the actions of the current authorization are used.
     * @return static
     */
    public function createChildAuthorization(Resource $resource, Actions $actions = null)
    {
        $actions = $actions ?: $this->actions;

        $authorization = self::create($this->role, $actions, $resource);

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
     * @return null|string
     */
    public function getEntityField()
    {
        return $this->entityField;
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
