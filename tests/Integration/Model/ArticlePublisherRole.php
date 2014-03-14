<?php

namespace Tests\MyCLabs\ACL\Integration\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use MyCLabs\ACL\Model\Actions;
use MyCLabs\ACL\Model\Authorization;
use MyCLabs\ACL\Model\EntityFieldResource;
use MyCLabs\ACL\Model\Role;

/**
 * @ORM\Entity(readOnly=true)
 */
class ArticlePublisherRole extends Role
{
    /**
     * @var Article
     * @ORM\ManyToOne(targetEntity="Article", inversedBy="roles")
     */
    protected $article;

    public function __construct(User $identity, Article $article)
    {
        $this->article = $article;

        parent::__construct($identity);
    }

    public function createAuthorizations(EntityManager $entityManager)
    {
        $authorizations = [];

        // The publisher can view the article
        $authorizations[] = Authorization::create(
            $this,
            new Actions([Actions::VIEW]),
            $this->article
        );

        // The publisher can publish the article
        $authorizations[] = Authorization::create(
            $this,
            new Actions([Actions::EDIT]),
            new EntityFieldResource($this->article, 'published')
        );

        return $authorizations;
    }

    /**
     * @return Article
     */
    public function getArticle()
    {
        return $this->article;
    }
}
