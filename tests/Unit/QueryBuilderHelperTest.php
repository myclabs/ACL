<?php

namespace Tests\MyCLabs\ACL\Unit;

use Doctrine\ORM\QueryBuilder;
use MyCLabs\ACL\Model\Actions;
use MyCLabs\ACL\QueryBuilderHelper;

/**
 * @covers \MyCLabs\ACL\QueryBuilderHelper
 */
class QueryBuilderHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testJoinACL()
    {
        $em = $this->getMock('Doctrine\ORM\EntityManager', [], [], '', false);
        $identity = $this->getMockForAbstractClass('MyCLabs\ACL\Model\SecurityIdentityInterface');

        $qb = new QueryBuilder($em);

        $qb->select('test')
            ->from('test', 'test');

        QueryBuilderHelper::joinACL($qb, 'test', 'test', $identity, Actions::VIEW);

        $dql = 'SELECT test FROM test test INNER JOIN MyCLabs\ACL\Model\Authorization authorization '
            . 'WITH test.id = authorization.entityId '
            . 'WHERE authorization.entityClass = :acl_entity_class '
            . 'AND authorization.securityIdentity = :acl_identity '
            . 'AND authorization.actions.view = true';
        $this->assertEquals($dql, $qb->getDQL());

        $this->assertSame('test', $qb->getParameter('acl_entity_class')->getValue());
        $this->assertSame($identity, $qb->getParameter('acl_identity')->getValue());
    }
}
