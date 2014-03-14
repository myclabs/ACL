<?php

namespace Tests\MyCLabs\ACL\Integration;

use MyCLabs\ACL\Model\Actions;
use MyCLabs\ACL\Model\Resource;
use Tests\MyCLabs\ACL\Integration\Model\Article;
use Tests\MyCLabs\ACL\Integration\Model\ArticlePublisherRole;
use Tests\MyCLabs\ACL\Integration\Model\CommentArticlesRole;
use Tests\MyCLabs\ACL\Integration\Model\User;

/**
 * Tests authorizations applied on the field of a class.
 *
 * @coversNothing
 */
class ClassFieldScopeTest extends AbstractIntegrationTest
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

        $this->aclManager->grant($user, new CommentArticlesRole($user));

        $this->assertFalse($this->aclManager->isAllowed($user, Actions::VIEW, $article1));
        $this->assertFalse($this->aclManager->isAllowed($user, Actions::EDIT, $article1));
        $this->assertFalse($this->aclManager->isAllowed($user, Actions::DELETE, $article1));
        $this->assertFalse($this->aclManager->isAllowed($user, Actions::VIEW, $article2));
        $this->assertFalse($this->aclManager->isAllowed($user, Actions::EDIT, $article2));
        $this->assertFalse($this->aclManager->isAllowed($user, Actions::DELETE, $article2));

        // Check is allowed at class-field scope
        $fieldResource = Resource::fromEntityClassField('Tests\MyCLabs\ACL\Integration\Model\Article', 'comments');
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::EDIT, $fieldResource));

        // Check is allowed at entity-field scope
        $fieldResource = Resource::fromEntityField($article1, 'comments');
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::EDIT, $fieldResource));
        $fieldResource = Resource::fromEntityField($article2, 'comments');
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::EDIT, $fieldResource));
    }
}
