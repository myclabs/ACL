<?php

namespace Tests\MyCLabs\ACL\Integration;

use MyCLabs\ACL\Model\Actions;
use Tests\MyCLabs\ACL\Integration\Model\Article;
use Tests\MyCLabs\ACL\Integration\Model\User;

/**
 * @coversNothing
 */
class RevokedRoleTest extends AbstractIntegrationTest
{
    public function testRoleRevoked()
    {
        $article1 = new Article();
        $this->em->persist($article1);
        $article2 = new Article();
        $this->em->persist($article2);

        $user = new User();
        $this->em->persist($user);

        $this->em->flush();

        // Before: no access
        $this->assertFalse($this->acl->isAllowed($user, Actions::VIEW, $article1));
        $this->assertFalse($this->acl->isAllowed($user, Actions::EDIT, $article1));
        $this->assertFalse($this->acl->isAllowed($user, Actions::VIEW, $article2));
        $this->assertFalse($this->acl->isAllowed($user, Actions::EDIT, $article2));

        // Role granted: access
        $this->acl->grant($user, 'ArticleEditor', $article2);
        $this->assertFalse($this->acl->isAllowed($user, Actions::VIEW, $article1));
        $this->assertFalse($this->acl->isAllowed($user, Actions::EDIT, $article1));
        $this->assertTrue($this->acl->isAllowed($user, Actions::VIEW, $article2));
        $this->assertTrue($this->acl->isAllowed($user, Actions::EDIT, $article2));

        // Role revoked: no access
        $this->acl->revoke($user, 'ArticleEditor', $article2);
        $this->assertFalse($this->acl->isAllowed($user, Actions::VIEW, $article1));
        $this->assertFalse($this->acl->isAllowed($user, Actions::EDIT, $article1));
        $this->assertFalse($this->acl->isAllowed($user, Actions::VIEW, $article2));
        $this->assertFalse($this->acl->isAllowed($user, Actions::EDIT, $article2));
    }
}
