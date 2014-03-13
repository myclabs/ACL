<?php

namespace Tests\MyCLabs\ACL\Integration\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use MyCLabs\ACL\Model\EntityResourceInterface;

/**
 * @ORM\Entity
 */
class Article implements EntityResourceInterface
{
    /**
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var ArticleEditorRole[]|Collection
     * @ORM\OneToMany(targetEntity="ArticleEditorRole", mappedBy="article")
     */
    protected $roles;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $published = false;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return boolean
     */
    public function getPublished()
    {
        return $this->published;
    }

    /**
     * @param boolean $published
     */
    public function setPublished($published)
    {
        $this->published = (boolean) $published;
    }
}
