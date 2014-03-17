<?php

namespace Tests\MyCLabs\ACL\Integration;

use MyCLabs\ACL\Model\Actions;
use Tests\MyCLabs\ACL\Integration\Model\Article;
use Tests\MyCLabs\ACL\Integration\Model\ArticleEditorRole;
use Tests\MyCLabs\ACL\Integration\Model\User;

/**
 * @coversNothing
 */
class RebuildAuthorizationTest extends AbstractIntegrationTest
{
    public function testRebuildAuthorizations()
    {
        $article1 = new Article();
        $this->em->persist($article1);
        $article2 = new Article();
        $this->em->persist($article2);

        $user = new User();
        $this->em->persist($user);
        $this->em->flush();

        $this->aclManager->grant($user, new ArticleEditorRole($user, $article2));

        $this->em->clear();

        $this->aclManager->rebuildAuthorizations();

        $this->assertFalse($this->aclManager->isAllowed($user, Actions::VIEW, $article1));
        $this->assertFalse($this->aclManager->isAllowed($user, Actions::EDIT, $article1));
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::VIEW, $article2));
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::EDIT, $article2));
    }
}
