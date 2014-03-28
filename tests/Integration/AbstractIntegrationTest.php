<?php

namespace Tests\MyCLabs\ACL\Integration;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use MyCLabs\ACL\ACLManager;
use MyCLabs\ACL\Doctrine\ACLSetup;

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

        $setup = new ACLSetup();
        $setup->setSecurityIdentityClass('Tests\MyCLabs\ACL\Integration\Model\User');

        $setup->registerRoleClass('Tests\MyCLabs\ACL\Integration\Model\ArticleEditorRole', 'articleEditor');
        $setup->registerRoleClass('Tests\MyCLabs\ACL\Integration\Model\AllArticlesEditorRole', 'allArticlesEditor');
        $setup->registerRoleClass('Tests\MyCLabs\ACL\Integration\Model\ArticlePublisherRole', 'articlePublisher');
        $setup->registerRoleClass('Tests\MyCLabs\ACL\Integration\Model\CategoryManagerRole', 'categoryManager');

        $setup->setActionsClass('Tests\MyCLabs\ACL\Integration\Model\Actions');

        // Create the entity manager
        $config = Setup::createAnnotationMetadataConfiguration($paths, true, null, new ArrayCache(), false);
        $this->em = EntityManager::create($dbParams, $config);

        $this->aclManager = new ACLManager($this->em);

        $setup->setUpEntityManager($this->em, function () {
            return $this->aclManager;
        });

        // Create the DB
        $tool = new SchemaTool($this->em);
        $tool->createSchema($this->em->getMetadataFactory()->getAllMetadata());
    }
}
