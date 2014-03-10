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

        QueryBuilderHelper::joinACL($qb, 'test', $identity, Actions::VIEW);

        $dql = 'SELECT test FROM test test INNER JOIN test.authorizations authorization '
            . 'WHERE authorization.securityIdentity = :acl_identity AND authorization.actions.view = true';
        $this->assertEquals($dql, $qb->getDQL());

        $this->assertSame($identity, $qb->getParameter('acl_identity')->getValue());
    }
}
