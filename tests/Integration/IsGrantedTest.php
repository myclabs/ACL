<?php

namespace Tests\MyCLabs\ACL\Integration;

use Tests\MyCLabs\ACL\Integration\Model\Article;
use Tests\MyCLabs\ACL\Integration\Model\User;

/**
 * @covers \MyCLabs\ACL\ACL::isGranted
 */
class IsGrantedTest extends AbstractIntegrationTest
{
    public function testIsGranted()
    {
        $article1 = new Article();
        $this->em->persist($article1);
        $article2 = new Article();
        $this->em->persist($article2);

        $user = new User();
        $this->em->persist($user);

        $this->em->flush();

        // Before: no access
        $this->assertFalse($this->acl->isGranted($user, 'ArticleEditor', $article1));
        $this->assertFalse($this->acl->isGranted($user, 'ArticleEditor', $article2));
        $this->assertFalse($this->acl->isGranted($user, 'AllArticlesEditor'));

        // Roles granted
        $this->acl->grant($user, 'ArticleEditor', $article2);
        $this->acl->grant($user, 'AllArticlesEditor');
        $this->assertFalse($this->acl->isGranted($user, 'ArticleEditor', $article1));
        $this->assertTrue($this->acl->isGranted($user, 'ArticleEditor', $article2));
        $this->assertTrue($this->acl->isGranted($user, 'AllArticlesEditor'));

        // Role revoked
        $this->acl->revoke($user, 'ArticleEditor', $article2);
        $this->acl->revoke($user, 'AllArticlesEditor');
        $this->assertFalse($this->acl->isGranted($user, 'ArticleEditor', $article1));
        $this->assertFalse($this->acl->isGranted($user, 'ArticleEditor', $article2));
        $this->assertFalse($this->acl->isGranted($user, 'AllArticlesEditor'));
    }
}
