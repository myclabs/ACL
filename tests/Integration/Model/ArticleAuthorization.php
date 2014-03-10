<?php

namespace Tests\MyCLabs\ACL\Integration\Model;

use MyCLabs\ACL\Model\Authorization;

/**
 * @Entity(readOnly=true)
 */
class ArticleAuthorization extends Authorization
{
    /**
     * @var Article|null
     * @ManyToOne(targetEntity="Article", inversedBy="authorizations")
     * @JoinColumn(onDelete="CASCADE")
     */
    protected $resource;
}
