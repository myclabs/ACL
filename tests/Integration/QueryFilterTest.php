<?php

namespace Tests\MyCLabs\ACL\Integration;

use MyCLabs\ACL\Doctrine\ACLQueryHelper;
use MyCLabs\ACL\Model\Actions;
use MyCLabs\ACL\Model\Authorization;
use Tests\MyCLabs\ACL\Integration\Model\Article;
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

        $this->acl->grant($user, 'ArticleEditor', $article2);

        $query = $this->em->createQuery(
            'SELECT a FROM ' . Model\Article::class . ' a
            JOIN ' . Authorization::class . ' authorization WITH a.id = authorization.resource.id
            WHERE authorization.securityIdentity = :identity
            AND authorization.resource.name = :resourceName
            AND authorization.actions.view = true'
        );
        $query->setParameter('resourceName', Model\Article::class);
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

        $this->acl->grant($user, 'ArticleEditor', $article2);

        $qb = $this->em->createQueryBuilder();
        $qb->select('a')->from(Model\Article::class, 'a');
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
        $this->acl->grant($user, 'ArticleEditor', $article2);
        $this->acl->grant($user, 'ArticleEditorCopy', $article2);

        // Check that there is really 2 authorizations created
        $authorizations = $this->em->getRepository(Authorization::class)->findAll();
        $this->assertCount(2, $authorizations);

        $qb = $this->em->createQueryBuilder();
        $qb->select('a')->from(Model\Article::class, 'a');
        ACLQueryHelper::joinACL($qb, $user, Actions::VIEW);
        $articles = $qb->getQuery()->getResult();

        // Check that we get the article only once
        $this->assertCount(1, $articles);
        $this->assertSame($article2, current($articles));
    }
}
