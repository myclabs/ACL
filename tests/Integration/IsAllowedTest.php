<?php

namespace Tests\MyCLabs\ACL\Integration;

use MyCLabs\ACL\Model\Action;
use Tests\MyCLabs\ACL\Integration\Model\Article;
use Tests\MyCLabs\ACL\Integration\Model\ArticleEditorRole;
use Tests\MyCLabs\ACL\Integration\Model\User;

/**
 * @coversNothing
 */
class IsAllowedTest extends AbstractIntegrationTest
{
    public function testWithFlush()
    {
        $article1 = new Article();
        $this->em->persist($article1);
        $article2 = new Article();
        $this->em->persist($article2);

        $user = new User();
        $this->em->persist($user);

        $this->aclService->addRole($user, new ArticleEditorRole($user, $article2));

        $this->em->flush();

        $this->assertFalse($this->aclService->isAllowed($user, Action::VIEW(), $article1));
        $this->assertFalse($this->aclService->isAllowed($user, Action::EDIT(), $article1));
        $this->assertTrue($this->aclService->isAllowed($user, Action::VIEW(), $article2));
        $this->assertTrue($this->aclService->isAllowed($user, Action::EDIT(), $article2));
    }

    public function testWithoutFlush()
    {
        $article1 = new Article();
        $article2 = new Article();

        $user = new User();

        $this->aclService->addRole($user, new ArticleEditorRole($user, $article2));

        $this->assertFalse($this->aclService->isAllowed($user, Action::VIEW(), $article1));
        $this->assertFalse($this->aclService->isAllowed($user, Action::EDIT(), $article1));
        $this->assertTrue($this->aclService->isAllowed($user, Action::VIEW(), $article2));
        $this->assertTrue($this->aclService->isAllowed($user, Action::EDIT(), $article2));
    }
}
