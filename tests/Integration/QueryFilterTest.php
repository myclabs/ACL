<?php

namespace Tests\MyCLabs\ACL\Integration;

use MyCLabs\ACL\Model\Action;
use MyCLabs\ACL\Model\Actions;
use MyCLabs\ACL\QueryBuilderHelper;
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

        $this->aclManager->addRole($user, new ArticleEditorRole($user, $article2));

        $this->em->flush();

        $query = $this->em->createQuery(
            'SELECT a
            FROM Tests\MyCLabs\ACL\Integration\Model\Article a
            JOIN a.authorizations auth
            WHERE auth.securityIdentity = :identity
            AND auth.actions.view = true'
        );
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

        $this->aclManager->addRole($user, new ArticleEditorRole($user, $article2));

        $this->em->flush();

        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from('Tests\MyCLabs\ACL\Integration\Model\Article', 'a');
        QueryBuilderHelper::joinACL($qb, 'a', $user, Actions::VIEW);
        $articles = $qb->getQuery()->getResult();

        $this->assertCount(1, $articles);
        $this->assertSame($article2, current($articles));
    }
}
