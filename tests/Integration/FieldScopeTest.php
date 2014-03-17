<?php

namespace Tests\MyCLabs\ACL\Integration;

use MyCLabs\ACL\Model\Actions;
use MyCLabs\ACL\Model\EntityFieldResource;
use Tests\MyCLabs\ACL\Integration\Model\Article;
use Tests\MyCLabs\ACL\Integration\Model\ArticlePublisherRole;
use Tests\MyCLabs\ACL\Integration\Model\User;

/**
 * Tests authorizations applied on the field of an entity.
 *
 * @coversNothing
 */
class FieldScopeTest extends AbstractIntegrationTest
{
    public function testFieldScope()
    {
        $article1 = new Article();
        $this->em->persist($article1);
        $article2 = new Article();
        $this->em->persist($article2);

        $user = new User();
        $this->em->persist($user);

        $this->em->flush();

        $this->aclManager->grant($user, new ArticlePublisherRole($user, $article2));

        // Test on entities
        $this->assertFalse($this->aclManager->isAllowed($user, Actions::VIEW, $article1));
        $this->assertFalse($this->aclManager->isAllowed($user, Actions::EDIT, $article1));
        $this->assertFalse($this->aclManager->isAllowed($user, Actions::DELETE, $article1));
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::VIEW, $article2));
        $this->assertFalse($this->aclManager->isAllowed($user, Actions::EDIT, $article2));
        $this->assertFalse($this->aclManager->isAllowed($user, Actions::DELETE, $article2));

        // Test on the field
        $fieldResource = new EntityFieldResource($article2, 'published');
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::EDIT, $fieldResource));
    }
}
