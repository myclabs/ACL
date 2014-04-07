<?php

namespace Tests\MyCLabs\ACL\Integration\Model;

use Doctrine\ORM\Mapping as ORM;
use MyCLabs\ACL\ACL;
use MyCLabs\ACL\Model\ClassResource;
use MyCLabs\ACL\Model\Role;

/**
 * @ORM\Entity(readOnly=true)
 */
class AllArticlesEditorRole extends Role
{
    public function createAuthorizations(ACL $acl)
    {
        $acl->allow(
            $this,
            new Actions([Actions::VIEW, Actions::EDIT]),
            new ClassResource('Tests\MyCLabs\ACL\Integration\Model\Article')
        );
    }
}
