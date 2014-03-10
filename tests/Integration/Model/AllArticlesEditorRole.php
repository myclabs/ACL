<?php

namespace Tests\MyCLabs\ACL\Integration\Model;

use Doctrine\ORM\EntityManager;
use MyCLabs\ACL\Model\Actions;
use MyCLabs\ACL\Model\ResourceInterface;
use MyCLabs\ACL\Model\Role;
use MyCLabs\ACL\Model\SecurityIdentityInterface;

/**
 * @Entity
 */
class AllArticlesEditorRole extends Role
{
    /**
     * @var Article[]
     */
    private $articles;

    public function __construct(SecurityIdentityInterface $identity, array $articles)
    {
        $this->articles = $articles;

        parent::__construct($identity);
    }

    public function createAuthorizations(EntityManager $entityManager)
    {
        $parentAuthorization = ArticleAuthorization::create($this, new Actions([Actions::VIEW, Actions::EDIT]));

        $authorizations = [$parentAuthorization];

        // Apply on all articles
        foreach ($this->articles as $article) {
            $authorizations[] = ArticleAuthorization::createChildAuthorization($parentAuthorization, $article);
        }

        return $authorizations;
    }

    public function processNewResource(ResourceInterface $resource)
    {
        if (! $resource instanceof Article) {
            return [];
        }

        // Inherit authorizations
        $parentAuthorizations = $this->getRootAuthorizations();

        $authorizations = [];

        foreach ($parentAuthorizations as $parentAuthorization) {
            $authorizations[] = ArticleAuthorization::createChildAuthorization($parentAuthorization, $resource);
        }

        return $authorizations;
    }
}
