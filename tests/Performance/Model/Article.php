<?php

namespace Tests\MyCLabs\ACL\Performance\Model;

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
class Article implements EntityResource, CascadingResource
{
    /**
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var ArticleEditorRole[]|Collection
     * @ORM\OneToMany(targetEntity="ArticleEditorRole", mappedBy="article", cascade={"remove"})
     */
    protected $roles;

    /**
     * @var Category
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="articles")
     **/
    private $category;

    public function __construct(Category $category)
    {
        $this->roles = new ArrayCollection();
        $this->category = $category;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getParentResources(EntityManager $entityManager)
    {
        return [
            new ClassResource(get_class()),
            $this->category
        ];
    }

    public function getSubResources(EntityManager $entityManager)
    {
        return [];
    }
}
