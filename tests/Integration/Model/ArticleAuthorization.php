<?php

namespace Tests\MyCLabs\ACL\Integration\Model;

use MyCLabs\ACL\Model\Authorization;
use Tests\MyCLabs\ACL\Integration\Model\Article;

/**
 * @Entity
 */
class ArticleAuthorization extends Authorization
{
    /**
     * @var Article
     * @ManyToOne(targetEntity="Article", inversedBy="authorizations")
     * @JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected $resource;
}
