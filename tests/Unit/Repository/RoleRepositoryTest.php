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
use MyCLabs\ACL\Repository\RoleRepository;
use Tests\MyCLabs\ACL\Unit\Repository\Model\File;
use Tests\MyCLabs\ACL\Unit\Repository\Model\FileOwnerRole;
use Tests\MyCLabs\ACL\Unit\Repository\Model\User;

/**
 * @covers \MyCLabs\ACL\Repository\RoleRepository
 */
class RoleRepositoryTest extends \PHPUnit_Framework_TestCase
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

    public function testFindRolesDirectlyLinkedToResource()
    {
        $user = new User();
        $this->em->persist($user);
        $resource = new File();
        $this->em->persist($resource);
        $directRole = new FileOwnerRole($user, $resource);
        $this->em->persist($directRole);
        $parentRole = new FileOwnerRole($user, $resource);
        $this->em->persist($parentRole);
        $this->em->flush();

        $classResource = new ClassResource('\Tests\MyCLabs\ACL\Unit\Repository\Model\File');


        $parentView = Authorization::create($parentRole, new Actions([ Actions::VIEW ]), $classResource, true);

        $authorizations = [
            Authorization::create($directRole, new Actions([ Actions::EDIT ]), $resource, true),
            Authorization::create($directRole, new Actions([ Actions::DELETE ]), $resource, true),
            $parentView,
            $parentView->createChildAuthorization($resource)
        ];

        /** @var AuthorizationRepository $authorizationRepository */
        $authorizationRepository = $this->em->getRepository('MyCLabs\ACL\Model\Authorization');

        $authorizationRepository->insertBulk($authorizations);

        // Check user can VIEW and EDIT the Resource
        $this->assertTrue($authorizationRepository->isAllowedOnEntity($user, Actions::VIEW, $resource));
        $this->assertTrue($authorizationRepository->isAllowedOnEntity($user, Actions::EDIT, $resource));
        $this->assertTrue($authorizationRepository->isAllowedOnEntity($user, Actions::DELETE, $resource));

        // Check user can only VIEW the ClassResource
        $this->assertTrue($authorizationRepository->isAllowedOnEntityClass($user, Actions::VIEW, $classResource->getClass()));
        $this->assertFalse($authorizationRepository->isAllowedOnEntityClass($user, Actions::EDIT, $classResource->getClass()));
        $this->assertFalse($authorizationRepository->isAllowedOnEntityClass($user, Actions::DELETE, $classResource->getClass()));

        /** @var RoleRepository $roleRepository */
        $roleRepository = $this->em->getRepository('MyCLabs\ACL\Model\Role');

        // Test for entity resource
        $result = $roleRepository->findRolesDirectlyLinkedToResource($resource);
        $this->assertCount(1, $result);
        $this->assertSame($directRole, $result[0]);

        // Test for class resource
        $result = $roleRepository->findRolesDirectlyLinkedToResource($classResource);
        $this->assertCount(1, $result);
        $this->assertSame($parentRole, $result[0]);
    }
}
