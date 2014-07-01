<?php

namespace Tests\MyCLabs\ACL\Integration\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use MyCLabs\ACL\Model\CascadingResource;
use MyCLabs\ACL\Model\ClassResource;
use MyCLabs\ACL\Model\EntityResourceTrait;
use MyCLabs\ACL\Model\ResourceInterface;

/**
 * @ORM\Entity
 */
class Category implements ResourceInterface, CascadingResource
{
    use EntityResourceTrait;

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

    public function getParentResources()
    {
        $parents = [ new ClassResource(get_class()) ];

        if ($this->parent !== null) {
            $parents[] = $this->parent;
        }

        return $parents;
    }

    public function getSubResources()
    {
        return $this->children->toArray();
    }
}
