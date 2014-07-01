<?php

namespace Tests\MyCLabs\ACL\Unit\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use MyCLabs\ACL\Doctrine\ACLQueryHelper;
use MyCLabs\ACL\Model\Actions;
use MyCLabs\ACL\Model\Authorization;
use MyCLabs\ACL\Model\Identity;

/**
 * @covers \MyCLabs\ACL\Doctrine\ACLQueryHelper
 */
class ACLQueryHelperTest extends \PHPUnit_Framework_TestCase
{
    public function testJoinACL()
    {
        $em = $this->getMock(EntityManager::class, [], [], '', false);
        $identity = $this->getMockForAbstractClass(Identity::class);

        $qb = new QueryBuilder($em);

        $qb->select('test')
            ->from('test', 'test');

        ACLQueryHelper::joinACL($qb, $identity, Actions::VIEW, 'test', 'test');

        $dql = 'SELECT test FROM test test INNER JOIN ' . Authorization::class . ' authorization '
            . 'WITH test.id = authorization.resource.id '
            . 'WHERE authorization.resource.name = :acl_resource_name '
            . 'AND authorization.identity = :acl_identity '
            . 'AND authorization.actions.view = true';
        $this->assertEquals($dql, $qb->getDQL());

        $this->assertSame('test', $qb->getParameter('acl_resource_name')->getValue());
        $this->assertSame($identity, $qb->getParameter('acl_identity')->getValue());
    }

    public function testJoinACLWithoutEntityAliasAndClass()
    {
        $em = $this->getMock(EntityManager::class, [], [], '', false);
        $identity = $this->getMockForAbstractClass(Identity::class);

        $qb = new QueryBuilder($em);

        $qb->select('test')
            ->from('test', 'test');

        ACLQueryHelper::joinACL($qb, $identity, Actions::VIEW);

        $dql = 'SELECT test FROM test test INNER JOIN ' . Authorization::class . ' authorization '
            . 'WITH test.id = authorization.resource.id '
            . 'WHERE authorization.resource.name = :acl_resource_name '
            . 'AND authorization.identity = :acl_identity '
            . 'AND authorization.actions.view = true';
        $this->assertEquals($dql, $qb->getDQL());

        $this->assertSame('test', $qb->getParameter('acl_resource_name')->getValue());
        $this->assertSame($identity, $qb->getParameter('acl_identity')->getValue());
    }
}
