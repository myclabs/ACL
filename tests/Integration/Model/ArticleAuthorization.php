<?php

namespace Tests\MyCLabs\ACL\Integration\Model;

use Doctrine\ORM\Mapping as ORM;
use MyCLabs\ACL\Model\Authorization;

/**
 * @ORM\Entity(readOnly=true)
 */
class ArticleAuthorization extends Authorization
{
    /**
     * @var Article|null
     * @ORM\ManyToOne(targetEntity="Article", inversedBy="authorizations")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $resource;
}
