<?php

namespace Tests\MyCLabs\ACL\Integration;

use MyCLabs\ACL\Model\Actions;
use Tests\MyCLabs\ACL\Integration\Model\AllArticlesEditorRole;
use Tests\MyCLabs\ACL\Integration\Model\Article;
use Tests\MyCLabs\ACL\Integration\Model\ArticleEditorRole;
use Tests\MyCLabs\ACL\Integration\Model\User;

/**
 * Tests authorizations applied at class scope (i.e. all entities of type X).
 *
 * @coversNothing
 */
class ClassScopeTest extends AbstractIntegrationTest
{
    /**
     * Check ACLs in the database, when the role is added before a flush
     */
    public function testFromDatabaseBeforeFlush()
    {
        $article1 = new Article();
        $this->em->persist($article1);
        $article2 = new Article();
        $this->em->persist($article2);

        $user = new User();
        $this->em->persist($user);

        $this->aclManager->addRole($user, new AllArticlesEditorRole($user, [$article1, $article2]));

        $this->em->flush();

        // Clear the entity manager and reload the entities so that we make sure we hit the database
        $this->em->clear();
        $article1 = $this->em->find(get_class($article1), $article1->getId());
        $article2 = $this->em->find(get_class($article2), $article2->getId());
        $user = $this->em->find(get_class($user), $user->getId());

        $this->assertTrue($this->aclManager->isAllowed($user, Actions::VIEW, $article1));
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::EDIT, $article1));
        $this->assertFalse($this->aclManager->isAllowed($user, Actions::DELETE, $article1));
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::VIEW, $article2));
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::EDIT, $article2));
        $this->assertFalse($this->aclManager->isAllowed($user, Actions::DELETE, $article2));
    }

    /**
     * Check ACLs in the database, when the role is added after a flush
     */
    public function testFromDatabaseAfterFlush()
    {
        $article1 = new Article();
        $this->em->persist($article1);
        $article2 = new Article();
        $this->em->persist($article2);

        $user = new User();
        $this->em->persist($user);

        $this->em->flush();

        $this->aclManager->addRole($user, new AllArticlesEditorRole($user, [$article1, $article2]));

        $this->em->flush();

        // Clear the entity manager and reload the entities so that we make sure we hit the database
        $this->em->clear();
        $article1 = $this->em->find(get_class($article1), $article1->getId());
        $article2 = $this->em->find(get_class($article2), $article2->getId());
        $user = $this->em->find(get_class($user), $user->getId());

        $this->assertTrue($this->aclManager->isAllowed($user, Actions::VIEW, $article1));
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::EDIT, $article1));
        $this->assertFalse($this->aclManager->isAllowed($user, Actions::DELETE, $article1));
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::VIEW, $article2));
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::EDIT, $article2));
        $this->assertFalse($this->aclManager->isAllowed($user, Actions::DELETE, $article2));
    }

    public function testFromMemory()
    {
        $this->markTestSkipped('Fails for now because of a bug in Doctrine 2.5');

        $article1 = new Article();
        $article2 = new Article();

        $user = new User();

        $this->aclManager->addRole($user, new ArticleEditorRole($user, $article2));

        $this->assertTrue($this->aclManager->isAllowed($user, Actions::VIEW, $article1));
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::EDIT, $article1));
        $this->assertFalse($this->aclManager->isAllowed($user, Actions::DELETE, $article1));
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::VIEW, $article2));
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::EDIT, $article2));
        $this->assertFalse($this->aclManager->isAllowed($user, Actions::DELETE, $article2));
    }

    /**
     * Check ACLs in the database after a new resource has been created
     */
    public function testNewResource()
    {
        $article1 = new Article();
        $this->em->persist($article1);

        $user = new User();
        $this->em->persist($user);

        $this->em->flush();

        $this->aclManager->addRole($user, new AllArticlesEditorRole($user, [$article1]));

        $this->em->flush();

        $article2 = new Article();
        $this->em->persist($article2);
        $this->aclManager->processNewResource($article2);
        $this->em->flush();

        // Clear the entity manager and reload the entities so that we make sure we hit the database
        $this->em->clear();
        $article1 = $this->em->find(get_class($article1), $article1->getId());
        $article2 = $this->em->find(get_class($article2), $article2->getId());
        $user = $this->em->find(get_class($user), $user->getId());

        $this->assertTrue($this->aclManager->isAllowed($user, Actions::VIEW, $article1));
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::EDIT, $article1));
        $this->assertFalse($this->aclManager->isAllowed($user, Actions::DELETE, $article1));
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::VIEW, $article2));
        $this->assertTrue($this->aclManager->isAllowed($user, Actions::EDIT, $article2));
        $this->assertFalse($this->aclManager->isAllowed($user, Actions::DELETE, $article2));
    }
}
