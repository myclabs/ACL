<?php

namespace Tests\MyCLabs\ACL\Integration\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use MyCLabs\ACL\Model\ResourceInterface;

/**
 * @ORM\Entity
 */
class Article implements ResourceInterface
{
    /**
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var ArticleAuthorization[]|Collection
     * @ORM\OneToMany(targetEntity="ArticleAuthorization", mappedBy="resource", fetch="EXTRA_LAZY")
     */
    protected $authorizations;

    /**
     * @var ArticleEditorRole[]|Collection
     * @ORM\OneToMany(targetEntity="ArticleEditorRole", mappedBy="article")
     */
    protected $roles;

    public function __construct()
    {
        $this->authorizations = new ArrayCollection();
        $this->roles = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }
}
