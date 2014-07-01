<?php

namespace Tests\MyCLabs\ACL\Integration;

use MyCLabs\ACL\Model\Actions;
use MyCLabs\ACL\Model\ClassResource;
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
        $user2 = new User();
        $this->em->persist($user2);

        $this->em->flush();

        $this->acl->grant($user, 'AllArticlesEditor');

        // Test on the resource class
        $classResource = new ClassResource(Model\Article::class);
        $this->assertTrue($this->acl->isAllowed($user, Actions::VIEW, $classResource));
        $this->assertTrue($this->acl->isAllowed($user, Actions::EDIT, $classResource));
        $this->assertFalse($this->acl->isAllowed($user, Actions::DELETE, $classResource));
        $this->assertFalse($this->acl->isAllowed($user2, Actions::VIEW, $classResource));

        // Test on entities
        $this->assertTrue($this->acl->isAllowed($user, Actions::VIEW, $article1));
        $this->assertTrue($this->acl->isAllowed($user, Actions::EDIT, $article1));
        $this->assertFalse($this->acl->isAllowed($user, Actions::DELETE, $article1));
        $this->assertTrue($this->acl->isAllowed($user, Actions::VIEW, $article2));
        $this->assertTrue($this->acl->isAllowed($user, Actions::EDIT, $article2));
        $this->assertFalse($this->acl->isAllowed($user, Actions::DELETE, $article2));
        $this->assertFalse($this->acl->isAllowed($user2, Actions::VIEW, $article1));
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

        $this->acl->grant($user, 'AllArticlesEditor');

        // Add a new resource after the role was given
        $article2 = new Article();
        $this->em->persist($article2);
        $this->em->flush();

        // Test on the resource class
        $classResource = new ClassResource(Model\Article::class);
        $this->assertTrue($this->acl->isAllowed($user, Actions::VIEW, $classResource));
        $this->assertTrue($this->acl->isAllowed($user, Actions::EDIT, $classResource));
        $this->assertFalse($this->acl->isAllowed($user, Actions::DELETE, $classResource));

        // Test on entities
        $this->assertTrue($this->acl->isAllowed($user, Actions::VIEW, $article1));
        $this->assertTrue($this->acl->isAllowed($user, Actions::EDIT, $article1));
        $this->assertFalse($this->acl->isAllowed($user, Actions::DELETE, $article1));
        $this->assertTrue($this->acl->isAllowed($user, Actions::VIEW, $article2));
        $this->assertTrue($this->acl->isAllowed($user, Actions::EDIT, $article2));
        $this->assertFalse($this->acl->isAllowed($user, Actions::DELETE, $article2));
    }

    public function testRevokeClassResource()
    {
        $article1 = new Article();
        $this->em->persist($article1);

        $user = new User();
        $this->em->persist($user);

        $this->em->flush();

        $this->acl->grant($user, 'AllArticlesEditor');
        $this->acl->revoke($user, 'AllArticlesEditor');

        // Test on the resource class
        $classResource = new ClassResource(Model\Article::class);
        $this->assertFalse($this->acl->isAllowed($user, Actions::VIEW, $classResource));

        // Test on entities
        $this->assertFalse($this->acl->isAllowed($user, Actions::VIEW, $article1));
    }
}
