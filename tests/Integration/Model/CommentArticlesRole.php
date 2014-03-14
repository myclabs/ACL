<?php

namespace Tests\MyCLabs\ACL\Integration\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use MyCLabs\ACL\Model\Actions;
use MyCLabs\ACL\Model\Authorization;
use MyCLabs\ACL\Model\ClassFieldResource;
use MyCLabs\ACL\Model\Role;

/**
 * Role that allows to comment all articles.
 *
 * @ORM\Entity(readOnly=true)
 */
class CommentArticlesRole extends Role
{
    public function createAuthorizations(EntityManager $entityManager)
    {
        return [Authorization::create(
            $this,
            new Actions([Actions::VIEW, Actions::EDIT]),
            new ClassFieldResource('Tests\MyCLabs\ACL\Integration\Model\Article', 'comments')
        )];
    }
}
