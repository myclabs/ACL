<?php

namespace Tests\MyCLabs\ACL\Integration;

use Tests\MyCLabs\ACL\Integration\Model\Actions;
use Tests\MyCLabs\ACL\Integration\Model\Article;
use Tests\MyCLabs\ACL\Integration\Model\User;

/**
 * @coversNothing
 */
class CustomActionsTest extends AbstractIntegrationTest
{
    public function testPublishArticle()
    {
        $article1 = new Article();
        $this->em->persist($article1);
        $article2 = new Article();
        $this->em->persist($article2);

        $user = new User();
        $this->em->persist($user);

        $this->em->flush();

        $this->acl->grant($user, 'articlePublisher', $article2);

        $this->assertFalse($this->acl->isAllowed($user, Actions::VIEW, $article1));
        $this->assertFalse($this->acl->isAllowed($user, Actions::PUBLISH, $article1));
        $this->assertFalse($this->acl->isAllowed($user, Actions::EDIT, $article1));

        $this->assertTrue($this->acl->isAllowed($user, Actions::VIEW, $article2));
        $this->assertTrue($this->acl->isAllowed($user, Actions::PUBLISH, $article2));
        $this->assertFalse($this->acl->isAllowed($user, Actions::EDIT, $article2));
    }
}
