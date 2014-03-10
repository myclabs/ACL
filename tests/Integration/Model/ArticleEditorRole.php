<?php

namespace Tests\MyCLabs\ACL\Integration\Model;

use MyCLabs\ACL\Model\Role;

/**
 * @Entity
 */
class ArticleEditorRole extends Role
{
    /**
     * @var Article
     * @ManyToOne(targetEntity="Article", inversedBy="roles")
     */
    protected $article;

    public function __construct(User $identity, Article $article)
    {
        $this->article = $article;

        parent::__construct($identity);
    }

    /**
     * @return Article
     */
    public function getArticle()
    {
        return $this->article;
    }
}
