<?php

namespace Tests\MyCLabs\ACL\Integration\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use MyCLabs\ACL\Model\ResourceInterface;
use MyCLabs\ACL\Model\ResourceTrait;

/**
 * @Entity
 */
class Article implements ResourceInterface
{
    use ResourceTrait;

    /**
     * @Id @GeneratedValue
     * @Column(type="integer")
     */
    protected $id;

    /**
     * @var ArticleAuthorization[]|Collection
     * @OneToMany(targetEntity="ArticleAuthorization", mappedBy="resource", fetch="EXTRA_LAZY")
     */
    protected $authorizations;

    /**
     * @var ArticleEditorRole[]|Collection
     * @OneToMany(targetEntity="ArticleEditorRole", mappedBy="article")
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
