<?php

namespace Tests\MyCLabs\ACL\Integration;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Tools\ResolveTargetEntityListener;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use MyCLabs\ACL\ACLManager;
use MyCLabs\ACL\MetadataLoader;
use Tests\MyCLabs\ACL\Integration\Model\ACLArticleListener;

abstract class AbstractIntegrationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var ACLManager
     */
    protected $aclManager;

    public function setUp()
    {
        $paths = [
            __DIR__ . '/../../src/Model',
            __DIR__ . '/Model',
        ];
        $dbParams = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $evm  = new EventManager();
        $rtel = new ResolveTargetEntityListener();
        $rtel->addResolveTargetEntity(
            'MyCLabs\ACL\Model\SecurityIdentityInterface',
            'Tests\MyCLabs\ACL\Integration\Model\User',
            []
        );

        $metadataLoader = new MetadataLoader();
        $metadataLoader->registerRoleClass('Tests\MyCLabs\ACL\Integration\Model\ArticleEditorRole', 'articleEditor');
        $metadataLoader->registerAuthorizationClass('Tests\MyCLabs\ACL\Integration\Model\ArticleAuthorization', 'article');

        $evm->addEventListener(Events::loadClassMetadata, $rtel);
        $evm->addEventListener(Events::loadClassMetadata, $metadataLoader);
        $evm->addEventListener(Events::onFlush, new ACLArticleListener());

        // Create the entity manager
        $config = Setup::createAnnotationMetadataConfiguration($paths, true, null, new ArrayCache(), false);
        $this->em = EntityManager::create($dbParams, $config, $evm);

        // Create the DB
        $tool = new SchemaTool($this->em);
        $tool->createSchema($this->em->getMetadataFactory()->getAllMetadata());

        $this->aclManager = new ACLManager($this->em);
    }
}
