<?php

namespace Tests\MyCLabs\ACL\Unit\Repository;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use MyCLabs\ACL\ACL;
use MyCLabs\ACL\Doctrine\ACLSetup;
use MyCLabs\ACL\Model\Actions;
use MyCLabs\ACL\Model\Authorization;
use MyCLabs\ACL\Model\ClassResource;
use MyCLabs\ACL\Repository\AuthorizationRepository;
use Tests\MyCLabs\ACL\Unit\Repository\Model\File;
use Tests\MyCLabs\ACL\Unit\Repository\Model\FileOwnerRole;
use Tests\MyCLabs\ACL\Unit\Repository\Model\User;

/**
 * @covers \MyCLabs\ACL\Repository\AuthorizationRepository
 */
class AuthorizationRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var ACL
     */
    private $acl;

    public function setUp()
    {
        $paths = [
            __DIR__ . '/../../../src/Model',
            __DIR__ . '/Model',
        ];
        $dbParams = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $setup = new ACLSetup();
        $setup->setSecurityIdentityClass('Tests\MyCLabs\ACL\Unit\Repository\Model\User');
        $setup->registerRoleClass('Tests\MyCLabs\ACL\Unit\Repository\Model\FileOwnerRole', 'fileOwner');

        // Create the entity manager
        $config = Setup::createAnnotationMetadataConfiguration($paths, true, null, new ArrayCache(), false);
        $this->em = EntityManager::create($dbParams, $config);

        $this->acl = new ACL($this->em);

        $setup->setUpEntityManager($this->em, function () {
            return $this->acl;
        });

        // Create the DB
        $tool = new SchemaTool($this->em);
        $tool->createSchema($this->em->getMetadataFactory()->getAllMetadata());
    }

    public function testInsertBulk()
    {
        $user = new User();
        $this->em->persist($user);
        $resource = new File();
        $this->em->persist($resource);
        $role = new FileOwnerRole($user, $resource);
        $this->em->persist($role);
        $this->em->flush();

        $authorizations = [
            Authorization::create($role, Actions::all(), $resource),
        ];

        /** @var AuthorizationRepository $repository */
        $repository = $this->em->getRepository('MyCLabs\ACL\Model\Authorization');

        $repository->insertBulk($authorizations);

        // Check that the authorization was inserted and can be retrieved
        $inserted = $repository->findAll();

        $this->assertCount(1, $inserted);

        /** @var Authorization $authorization */
        $authorization = $inserted[0];
        $this->assertSame($role, $authorization->getRole());
        $this->assertSame($user, $authorization->getSecurityIdentity());
        $this->assertEquals($resource->getId(), $authorization->getEntityId());
        $this->assertEquals('Tests\MyCLabs\ACL\Unit\Repository\Model\File', $authorization->getEntityClass());
        $this->assertEquals(Actions::all(), $authorization->getActions());
        $this->assertNull($authorization->getParentAuthorization());
        $this->assertEquals(0, count($authorization->getChildAuthorizations()));
        $this->assertTrue($authorization->isCascadable());
    }

    /**
     * @depends testInsertBulk
     */
    public function testFindCascadableAuthorizations()
    {
        $user = new User();
        $this->em->persist($user);
        $resource = new File();
        $this->em->persist($resource);
        $role = new FileOwnerRole($user, $resource);
        $this->em->persist($role);
        $this->em->flush();

        $classResource = new ClassResource('\Tests\MyCLabs\ACL\Unit\Repository\Model\File');

        $authorizations = [
            // VIEW cascades (entity resource)
            Authorization::create($role, new Actions([ Actions::VIEW ]), $resource, true),
            // EDIT doesn't cascade (entity resource)
            Authorization::create($role, new Actions([ Actions::EDIT ]), $resource, false),

            // VIEW cascades (class resource)
            Authorization::create($role, new Actions([ Actions::VIEW ]), $classResource, true),
            // EDIT doesn't cascade (class resource)
            Authorization::create($role, new Actions([ Actions::EDIT ]), $classResource, false),
        ];

        /** @var AuthorizationRepository $repository */
        $repository = $this->em->getRepository('MyCLabs\ACL\Model\Authorization');

        $repository->insertBulk($authorizations);

        // Test for entity resource
        $result = $repository->findCascadableAuthorizationsForResource($resource);
        $this->assertCount(1, $result);
        $this->assertTrue($result[0]->getActions()->view);
        $this->assertFalse($result[0]->getActions()->edit);

        // Test for class resource
        $result = $repository->findCascadableAuthorizationsForResource($classResource);
        $this->assertCount(1, $result);
        $this->assertTrue($result[0]->getActions()->view);
        $this->assertFalse($result[0]->getActions()->edit);
    }

    /**
     * @depends testInsertBulk
     */
    public function testIsAllowedOnEntity()
    {
        $user = new User();
        $this->em->persist($user);
        $resource = new File();
        $this->em->persist($resource);
        $role = new FileOwnerRole($user, $resource);
        $this->em->persist($role);
        $this->em->flush();

        $authorizations = [
            Authorization::create($role, new Actions([ Actions::VIEW ]), $resource),
        ];

        /** @var AuthorizationRepository $repository */
        $repository = $this->em->getRepository('MyCLabs\ACL\Model\Authorization');
        $repository->insertBulk($authorizations);

        $this->assertTrue($repository->isAllowedOnEntity($user, Actions::VIEW, $resource));
        $this->assertFalse($repository->isAllowedOnEntity($user, Actions::EDIT, $resource));
    }

    /**
     * @depends testInsertBulk
     */
    public function testIsAllowedOnEntityClass()
    {
        $user = new User();
        $this->em->persist($user);
        $resource = new File();
        $this->em->persist($resource);
        $role = new FileOwnerRole($user, $resource);
        $this->em->persist($role);
        $this->em->flush();

        $class = 'Tests\MyCLabs\ACL\Unit\Repository\Model\File';
        $classResource = new ClassResource($class);

        $authorizations = [
            Authorization::create($role, new Actions([ Actions::VIEW ]), $classResource),
        ];

        /** @var AuthorizationRepository $repository */
        $repository = $this->em->getRepository('MyCLabs\ACL\Model\Authorization');
        $repository->insertBulk($authorizations);

        $this->assertTrue($repository->isAllowedOnEntityClass($user, Actions::VIEW, $class));
        $this->assertFalse($repository->isAllowedOnEntityClass($user, Actions::EDIT, $class));
    }
}
