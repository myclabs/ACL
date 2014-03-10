<?php

namespace Tests\MyCLabs\ACL\Integration\Model;

use Doctrine\ORM\EntityManager;
use MyCLabs\ACL\Model\Actions;
use MyCLabs\ACL\Model\ResourceInterface;
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

    public function createAuthorizations(EntityManager $entityManager)
    {
        return [
            ArticleAuthorization::create($this, new Actions([Actions::VIEW, Actions::EDIT]), $this->article),
        ];
    }

    public function processNewResource(ResourceInterface $resource)
    {
        return [];
    }
}
