<?php

namespace Tests\MyCLabs\ACL\Integration\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use MyCLabs\ACL\Model\CascadingResource;
use MyCLabs\ACL\Model\ClassResource;
use MyCLabs\ACL\Model\EntityResource;

/**
 * @ORM\Entity
 */
class Category implements EntityResource, CascadingResource
{
    /**
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var Category[]
     * @ORM\OneToMany(targetEntity="Category", mappedBy="parent")
     **/
    private $children;

    /**
     * @var Category
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="children")
     **/
    private $parent;

    public function __construct(Category $parent = null)
    {
        $this->children = new ArrayCollection();

        if ($parent !== null) {
            $this->parent = $parent;
            $parent->children[] = $this;
        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function getParentResources(EntityManager $entityManager)
    {
        $parents = [ new ClassResource(get_class()) ];

        if ($this->parent !== null) {
            $parents[] = $this->parent;
        }

        return $parents;
    }

    public function getSubResources(EntityManager $entityManager)
    {
        return $this->children->toArray();
    }
}
