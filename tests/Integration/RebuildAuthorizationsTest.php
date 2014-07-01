<?php

namespace Tests\MyCLabs\ACL\Integration;

use MyCLabs\ACL\Model\Actions;
use MyCLabs\ACL\Model\Authorization;
use Tests\MyCLabs\ACL\Integration\Model\Article;
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

        $this->acl->grant($user, 'ArticleEditor', $article2);

        $this->em->clear();

        $qb = $this->em->createQueryBuilder();
        $qb->select('count(authorization.id)');
        $qb->from(Authorization::class, 'authorization');
        $query = $qb->getQuery();

        $initialCount = $query->getSingleScalarResult();

        $this->acl->rebuildAuthorizations();

        $this->assertFalse($this->acl->isAllowed($user, Actions::VIEW, $article1));
        $this->assertFalse($this->acl->isAllowed($user, Actions::EDIT, $article1));
        $this->assertTrue($this->acl->isAllowed($user, Actions::VIEW, $article2));
        $this->assertTrue($this->acl->isAllowed($user, Actions::EDIT, $article2));

        $this->assertEquals($initialCount, $query->getSingleScalarResult());
    }

    public function testRebuildAuthorizationsWithClassResource()
    {
        $article1 = new Article();
        $this->em->persist($article1);
        $article2 = new Article();
        $this->em->persist($article2);

        $user = new User();
        $this->em->persist($user);
        $this->em->flush();

        $this->acl->grant($user, 'AllArticlesEditor');

        $this->em->clear();

        $this->acl->rebuildAuthorizations();

        $this->assertTrue($this->acl->isAllowed($user, Actions::VIEW, $article1));
        $this->assertTrue($this->acl->isAllowed($user, Actions::EDIT, $article1));
        $this->assertTrue($this->acl->isAllowed($user, Actions::VIEW, $article2));
        $this->assertTrue($this->acl->isAllowed($user, Actions::EDIT, $article2));
    }

}
