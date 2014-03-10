<?php

namespace Tests\MyCLabs\ACL\Integration;

use MyCLabs\ACL\Model\Actions;
use Tests\MyCLabs\ACL\Integration\Model\Article;
use Tests\MyCLabs\ACL\Integration\Model\ArticleEditorRole;
use Tests\MyCLabs\ACL\Integration\Model\User;

/**
 * @coversNothing
 */
class IsAllowedTest extends AbstractIntegrationTest
{
    public function testAfterFlush()
    {
        $article1 = new Article();
        $this->em->persist($article1);
        $article2 = new Article();
        $this->em->persist($article2);

        $user = new User();
        $this->em->persist($user);

        $user->addRole(new ArticleEditorRole($user, $article2));

        $this->em->flush();

        // Clear the entity manager and reload the entities so that we make sure we hit the database
        $this->em->clear();
        $article1 = $this->em->find(get_class($article1), $article1->getId());
        $article2 = $this->em->find(get_class($article2), $article2->getId());
        $user = $this->em->find(get_class($user), $user->getId());

        $this->assertFalse($this->aclManager->isAllowed($user, Actions::VIEW, $article1));
        $this->assertFalse($this->aclManager->isAllowed($user, Actions::EDIT, $article1));
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::VIEW, $article2));
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::EDIT, $article2));
    }
}
