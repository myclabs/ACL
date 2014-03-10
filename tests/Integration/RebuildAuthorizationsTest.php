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
        $this->markTestSkipped('Fails for now because of a bug in Doctrine 2.5');

        $article1 = new Article();
        $this->em->persist($article1);
        $article2 = new Article();
        $this->em->persist($article2);

        $user = new User();
        $this->em->persist($user);

        $this->aclService->addRole($user, new ArticleEditorRole($user, $article2));

        $this->em->flush();
        $this->em->clear();

        $this->aclService->rebuildAuthorizations();

        $this->assertFalse($this->aclService->isAllowed($user, Actions::VIEW, $article1));
        $this->assertFalse($this->aclService->isAllowed($user, Actions::EDIT, $article1));
        $this->assertTrue($this->aclService->isAllowed($user, Actions::VIEW, $article2));
        $this->assertTrue($this->aclService->isAllowed($user, Actions::EDIT, $article2));
    }
}
