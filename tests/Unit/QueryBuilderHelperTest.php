<?php

namespace Tests\MyCLabs\ACL;

use Doctrine\ORM\QueryBuilder;
use MyCLabs\ACL\Model\Action;
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
        $action = Action::VIEW();

        $qb = new QueryBuilder($em);

        $qb->select('test')
            ->from('test', 'test');

        QueryBuilderHelper::joinACL($qb, 'test', $identity, $action);

        $dql = 'SELECT test FROM test test INNER JOIN test.authorizations authorization '
            . 'WHERE authorization.securityIdentity = :acl_identity AND authorization.actionId = :acl_actionId';
        $this->assertEquals($dql, $qb->getDQL());

        $this->assertSame($identity, $qb->getParameter('acl_identity')->getValue());
        $this->assertEquals($action->exportToString(), $qb->getParameter('acl_actionId')->getValue());
    }
}
