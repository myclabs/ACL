<?php

namespace Tests\MyCLabs\ACL\Integration;

use MyCLabs\ACL\Model\Actions;
use MyCLabs\ACL\Doctrine\ACLQueryHelper;
use Tests\MyCLabs\ACL\Integration\Model\Article;
use Tests\MyCLabs\ACL\Integration\Model\ArticleEditorRole;
use Tests\MyCLabs\ACL\Integration\Model\User;

/**
 * @coversNothing
 */
class QueryFilterTest extends AbstractIntegrationTest
{
    public function testFilter()
    {
        $article1 = new Article();
        $this->em->persist($article1);
        $article2 = new Article();
        $this->em->persist($article2);

        $user = new User();
        $this->em->persist($user);

        $this->em->flush();

        $this->acl->grant($user, 'articleEditor', $article2);

        $query = $this->em->createQuery(
            'SELECT a FROM Tests\MyCLabs\ACL\Integration\Model\Article a
            JOIN MyCLabs\ACL\Model\Authorization authorization WITH a.id = authorization.entityId
            WHERE authorization.securityIdentity = :identity
            AND authorization.entityClass = :entityClass
            AND authorization.actions.view = true'
        );
        $query->setParameter('entityClass', 'Tests\MyCLabs\ACL\Integration\Model\Article');
        $query->setParameter('identity', $user);
        $articles = $query->getResult();

        $this->assertCount(1, $articles);
        $this->assertSame($article2, current($articles));
    }

    public function testFilterUsingQueryBuilderHelper()
    {
        $article1 = new Article();
        $this->em->persist($article1);
        $article2 = new Article();
        $this->em->persist($article2);

        $user = new User();
        $this->em->persist($user);

        $this->em->flush();

        $this->acl->grant($user, 'articleEditor', $article2);

        $qb = $this->em->createQueryBuilder();
        $qb->select('a')->from('Tests\MyCLabs\ACL\Integration\Model\Article', 'a');
        ACLQueryHelper::joinACL($qb, $user, Actions::VIEW);
        $articles = $qb->getQuery()->getResult();

        $this->assertCount(1, $articles);
        $this->assertSame($article2, current($articles));
    }

    /**
     * @link https://github.com/myclabs/ACL/issues/8
     * @test
     */
    public function duplicateAuthorizationShouldNotYieldEntityTwice()
    {
        $article1 = new Article();
        $this->em->persist($article1);
        $article2 = new Article();
        $this->em->persist($article2);

        $user = new User();
        $this->em->persist($user);

        $this->em->flush();

        // Add the role twice => 2 authorizations for the same resource
        $this->acl->grant($user, 'articleEditor', $article2);
        $this->acl->grant($user, 'articleEditor', $article2);

        // Check that there is really 2 authorizations created
        $authorizations = $this->em->getRepository('MyCLabs\ACL\Model\Authorization')->findAll();
        $this->assertCount(2, $authorizations);

        $qb = $this->em->createQueryBuilder();
        $qb->select('a')->from('Tests\MyCLabs\ACL\Integration\Model\Article', 'a');
        ACLQueryHelper::joinACL($qb, $user, Actions::VIEW);
        $articles = $qb->getQuery()->getResult();

        // Check that we get the article only once
        $this->assertCount(1, $articles);
        $this->assertSame($article2, current($articles));
    }
}
