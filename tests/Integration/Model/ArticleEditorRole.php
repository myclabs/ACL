<?php

namespace Tests\MyCLabs\ACL\Integration\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use MyCLabs\ACL\Model\Actions;
use MyCLabs\ACL\Model\Authorization;
use MyCLabs\ACL\Model\Role;

/**
 * @ORM\Entity(readOnly=true)
 */
class ArticleEditorRole extends Role
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
        $editorActions = new Actions([Actions::VIEW, Actions::EDIT]);

        return [
            Authorization::create($this, $editorActions, $this->article),
        ];
    }

    /**
     * @return Article
     */
    public function getArticle()
    {
        return $this->article;
    }
}
