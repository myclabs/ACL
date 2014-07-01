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
use MyCLabs\ACL\Model\RoleEntry;
use MyCLabs\ACL\Repository\AuthorizationRepository;
use Tests\MyCLabs\ACL\Unit\Repository\Model\File;
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
        $setup->setIdentityClass(Model\User::class);
        $roles = [
            'fileOwner' => [
                'resourceType' => Model\File::class,
                'actions'      => new Actions([Actions::VIEW, Actions::EDIT])
            ]
        ];

        // Create the entity manager
        $config = Setup::createAnnotationMetadataConfiguration($paths, true, null, new ArrayCache(), false);
        $this->em = EntityManager::create($dbParams, $config);

        $this->acl = new ACL($this->em, $roles);

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
        $role = new RoleEntry($user, 'fileOwner', $resource);
        $this->em->persist($role);
        $this->em->flush();

        $authorizations = [
            Authorization::create($role, Actions::all(), $resource),
        ];

        /** @var AuthorizationRepository $repository */
        $repository = $this->em->getRepository(Authorization::class);

        $repository->insertBulk($authorizations);

        // Check that the authorization was inserted and can be retrieved
        $inserted = $repository->findAll();

        $this->assertCount(1, $inserted);

        /** @var Authorization $authorization */
        $authorization = $inserted[0];
        $this->assertSame($role, $authorization->getRoleEntry());
        $this->assertSame($user, $authorization->getIdentity());
        $this->assertEquals($resource->getId(), $authorization->getResourceId()->getId());
        $this->assertEquals(Model\File::class, $authorization->getResourceId()->getName());
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
        $role = new RoleEntry($user, 'owner', $resource);
        $this->em->persist($role);
        $this->em->flush();

        $classResource = new ClassResource(Model\File::class);

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
        $repository = $this->em->getRepository(Authorization::class);

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
    public function testHasAuthorizationOnEntity()
    {
        $user = new User();
        $this->em->persist($user);
        $resource = new File();
        $this->em->persist($resource);
        $role = new RoleEntry($user, 'owner', $resource);
        $this->em->persist($role);
        $this->em->flush();

        $authorizations = [
            Authorization::create($role, new Actions([ Actions::VIEW ]), $resource),
        ];

        /** @var AuthorizationRepository $repository */
        $repository = $this->em->getRepository(Authorization::class);
        $repository->insertBulk($authorizations);

        $this->assertTrue($repository->hasAuthorization($user, Actions::VIEW, $resource));
        $this->assertFalse($repository->hasAuthorization($user, Actions::EDIT, $resource));
    }

    /**
     * @depends testInsertBulk
     */
    public function testHasAuthorizationOnEntityClass()
    {
        $user = new User();
        $this->em->persist($user);
        $resource = new File();
        $this->em->persist($resource);
        $role = new RoleEntry($user, 'owner', $resource);
        $this->em->persist($role);
        $this->em->flush();

        $classResource = new ClassResource(Model\File::class);

        $authorizations = [
            Authorization::create($role, new Actions([ Actions::VIEW ]), $classResource),
        ];

        /** @var AuthorizationRepository $repository */
        $repository = $this->em->getRepository(Authorization::class);
        $repository->insertBulk($authorizations);

        $this->assertTrue($repository->hasAuthorization($user, Actions::VIEW, $classResource));
        $this->assertFalse($repository->hasAuthorization($user, Actions::EDIT, $classResource));
    }

    /**
     * @depends testInsertBulk
     */
    public function testRemoveForResource()
    {
        $user = new User();
        $this->em->persist($user);

        $resource1 = new File();
        $this->em->persist($resource1);
        $role1 = new RoleEntry($user, 'owner', $resource1);
        $this->em->persist($role1);
        $this->em->flush();

        $resource2 = new File();
        $this->em->persist($resource2);
        $role2 = new RoleEntry($user, 'owner', $resource2);
        $this->em->persist($role2);
        $this->em->flush();

        $authorizations = [
            Authorization::create($role1, new Actions([ Actions::VIEW ]), $resource1),
            Authorization::create($role2, new Actions([ Actions::VIEW ]), $resource2),
        ];

        /** @var AuthorizationRepository $repository */
        $repository = $this->em->getRepository(Authorization::class);
        $repository->insertBulk($authorizations);

        // We remove the authorizations for the resource 1
        $repository->removeForResource($resource1);
        // We check that they were removed
        $this->assertFalse($repository->hasAuthorization($user, Actions::VIEW, $resource1));
        // and that authorizations for the resource 2 weren't removed
        $this->assertTrue($repository->hasAuthorization($user, Actions::VIEW, $resource2));
    }
}
