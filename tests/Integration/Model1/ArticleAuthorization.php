<?php

namespace Tests\MyCLabs\ACL\Integration;

use MyCLabs\ACL\Model\Authorization;

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
