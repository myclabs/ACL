<?php

namespace Tests\MyCLabs\ACL;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Tools\ResolveTargetEntityListener;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use MyCLabs\ACL\ACLService;
use MyCLabs\ACL\MetadataLoader;
use MyCLabs\ACL\Model\Action;
use Tests\MyCLabs\ACL\Integration\Article;
use Tests\MyCLabs\ACL\Integration\ArticleEditorRole;
use Tests\MyCLabs\ACL\Integration\User;

/**
 * @coversNothing
 */
class SimpleIntegrationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var ACLService
     */
    private $aclService;

    public function setUp()
    {
        $paths = [
            __DIR__ . '/../../src/Model',
            __DIR__ . '/Model1',
        ];
        $dbParams = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $evm  = new EventManager();
        $rtel = new ResolveTargetEntityListener();
        $rtel->addResolveTargetEntity(
            'MyCLabs\ACL\Model\SecurityIdentityInterface',
            'Tests\MyCLabs\ACL\Integration\User',
            []
        );

        $metadataLoader = new MetadataLoader();
        $metadataLoader->registerRoleClass('Tests\MyCLabs\ACL\Integration\ArticleEditorRole', 'articleEditor');
        $metadataLoader->registerAuthorizationClass('Tests\MyCLabs\ACL\Integration\ArticleAuthorization', 'article');

        $evm->addEventListener(Events::loadClassMetadata, $rtel);
        $evm->addEventListener(Events::loadClassMetadata, $metadataLoader);

        // Create the entity manager
        $config = Setup::createAnnotationMetadataConfiguration($paths, true);
        $this->em = EntityManager::create($dbParams, $config, $evm);

        // Create the DB
        $tool = new SchemaTool($this->em);
        $tool->createSchema($this->em->getMetadataFactory()->getAllMetadata());

        $this->aclService = new ACLService($this->em);
    }

    public function testIsAllowedWithFlush()
    {
        $article1 = new Article();
        $this->em->persist($article1);
        $article2 = new Article();
        $this->em->persist($article2);

        $user = new User();
        $this->em->persist($user);

        $this->aclService->addRole($user, new ArticleEditorRole($user, $article2));

        $this->em->flush();

        $this->assertFalse($this->aclService->isAllowed($user, Action::VIEW(), $article1));
        $this->assertFalse($this->aclService->isAllowed($user, Action::EDIT(), $article1));
        $this->assertTrue($this->aclService->isAllowed($user, Action::VIEW(), $article2));
        $this->assertTrue($this->aclService->isAllowed($user, Action::EDIT(), $article2));
    }

    public function testIsAllowedWithoutFlush()
    {
        $article1 = new Article();
        $article2 = new Article();

        $user = new User();

        $this->aclService->addRole($user, new ArticleEditorRole($user, $article2));

        $this->assertFalse($this->aclService->isAllowed($user, Action::VIEW(), $article1));
        $this->assertFalse($this->aclService->isAllowed($user, Action::EDIT(), $article1));
        $this->assertTrue($this->aclService->isAllowed($user, Action::VIEW(), $article2));
        $this->assertTrue($this->aclService->isAllowed($user, Action::EDIT(), $article2));
    }

    public function testRebuildAuthorizations()
    {
        $article1 = new Article();
        $this->em->persist($article1);
        $article2 = new Article();
        $this->em->persist($article2);

        $user = new User();
        $this->em->persist($user);

        $this->aclService->addRole($user, new ArticleEditorRole($user, $article2));

        $this->em->flush();
        $this->em->clear();

        $this->aclService->rebuildAuthorizations();

        $this->assertFalse($this->aclService->isAllowed($user, Action::VIEW(), $article1));
        $this->assertFalse($this->aclService->isAllowed($user, Action::EDIT(), $article1));
        $this->assertTrue($this->aclService->isAllowed($user, Action::VIEW(), $article2));
        $this->assertTrue($this->aclService->isAllowed($user, Action::EDIT(), $article2));
    }
}
