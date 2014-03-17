<?php

namespace Tests\MyCLabs\ACL\Integration\Model;

use Doctrine\ORM\Mapping as ORM;
use MyCLabs\ACL\ACLManager;
use MyCLabs\ACL\Model\Actions;
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

    public function createAuthorizations(ACLManager $aclManager)
    {
        // The publisher can view the article
        $aclManager->allow($this, new Actions([Actions::VIEW]), $this->article);

        // The publisher can publish the article
        $aclManager->allow($this, new Actions([Actions::EDIT]), new EntityFieldResource($this->article, 'published'));
    }

    /**
     * @return Article
     */
    public function getArticle()
    {
        return $this->article;
    }
}
