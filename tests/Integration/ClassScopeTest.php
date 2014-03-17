<?php

namespace Tests\MyCLabs\ACL\Integration;

use MyCLabs\ACL\Model\Actions;
use MyCLabs\ACL\Model\ClassResource;
use Tests\MyCLabs\ACL\Integration\Model\AllArticlesEditorRole;
use Tests\MyCLabs\ACL\Integration\Model\Article;
use Tests\MyCLabs\ACL\Integration\Model\User;

/**
 * Tests authorizations applied at class scope (i.e. all entities of type X).
 *
 * @coversNothing
 */
class ClassScopeTest extends AbstractIntegrationTest
{
    /**
     * Check when adding the role and all entities are already created and flushed
     */
    public function testExistingResources()
    {
        $article1 = new Article();
        $this->em->persist($article1);
        $article2 = new Article();
        $this->em->persist($article2);

        $user = new User();
        $this->em->persist($user);

        $this->em->flush();

        $this->aclManager->grant($user, new AllArticlesEditorRole($user));

        // Test on the resource class
        $classResource = new ClassResource('Tests\MyCLabs\ACL\Integration\Model\Article');
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::VIEW, $classResource));
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::EDIT, $classResource));
        $this->assertFalse($this->aclManager->isAllowed($user, Actions::DELETE, $classResource));

        // Test on entities
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::VIEW, $article1));
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::EDIT, $article1));
        $this->assertFalse($this->aclManager->isAllowed($user, Actions::DELETE, $article1));
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::VIEW, $article2));
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::EDIT, $article2));
        $this->assertFalse($this->aclManager->isAllowed($user, Actions::DELETE, $article2));
    }

    /**
     * Check after a new resource has been created (role was added before)
     */
    public function testNewResource()
    {
        $article1 = new Article();
        $this->em->persist($article1);

        $user = new User();
        $this->em->persist($user);

        $this->em->flush();

        $this->aclManager->grant($user, new AllArticlesEditorRole($user));

        // Add a new resource after the role was given
        $article2 = new Article();
        $this->em->persist($article2);
        $this->em->flush();

        // Test on the resource class
        $classResource = new ClassResource('Tests\MyCLabs\ACL\Integration\Model\Article');
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::VIEW, $classResource));
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::EDIT, $classResource));
        $this->assertFalse($this->aclManager->isAllowed($user, Actions::DELETE, $classResource));

        // Test on entities
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::VIEW, $article1));
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::EDIT, $article1));
        $this->assertFalse($this->aclManager->isAllowed($user, Actions::DELETE, $article1));
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::VIEW, $article2));
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::EDIT, $article2));
        $this->assertFalse($this->aclManager->isAllowed($user, Actions::DELETE, $article2));
    }
}
