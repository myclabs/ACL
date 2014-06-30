<?php

namespace Tests\MyCLabs\ACL\Integration;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use MyCLabs\ACL\ACL;
use MyCLabs\ACL\Doctrine\ACLSetup;
use MyCLabs\ACL\Model\ClassResource;
use MyCLabs\ACL\Model\VirtualResource;
use Tests\MyCLabs\ACL\Integration\Model\Actions;

abstract class AbstractIntegrationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var ACL
     */
    protected $acl;

    public function setUp()
    {
        $dbParams = $this->getDBParams();

        // Create the DB
        $this->dropAndCreateDB(DriverManager::getConnection($dbParams));

        // Create the entity manager
        $paths = [
            __DIR__ . '/../../src/Model',
            __DIR__ . '/Model',
            __DIR__ . '/Issues/Issue10',
        ];
        $config = Setup::createAnnotationMetadataConfiguration($paths, true, null, new ArrayCache(), false);
        $this->em = EntityManager::create($dbParams, $config);

        // Create the ACL object
        $this->acl = $this->createACL();
        $setup = $this->configureACL();
        $setup->setUpEntityManager($this->em, function () {
            return $this->acl;
        });

        // Create the schema
        $tool = new SchemaTool($this->em);
        $tool->createSchema($this->em->getMetadataFactory()->getAllMetadata());

        // Necessary so that SQLite supports CASCADE DELETE
        if ($dbParams['driver'] == 'pdo_sqlite') {
            $this->em->getConnection()->executeQuery('PRAGMA foreign_keys = ON');
        }
    }

    protected function createACL()
    {
        return new ACL($this->em);
    }

    private function configureACL()
    {
        $setup = new ACLSetup();
        $setup->setIdentityClass(Model\User::class);

        $roles = [
            'ArticleEditor' => [
                'resourceType' => Model\Article::class,
                'actions' => new Actions([Actions::VIEW, Actions::EDIT])
            ],
            'AllArticlesEditor' => [
                'resource' => new ClassResource(Model\Article::class),
                'actions' => new Actions([Actions::VIEW, Actions::EDIT])
            ],
            'ArticlePublisher' => [
                'resourceType' => Model\Article::class,
                'actions' => new Actions([Actions::VIEW, Actions::PUBLISH])
            ],
            'CategoryManager' => [
                'resourceType' => Model\Category::class,
                'actions' => new Actions([Actions::VIEW])
            ],
            'ArticleEditorCopy' => [
                'resourceType' => Model\Article::class,
                'actions' => new Actions([Actions::VIEW, Actions::EDIT])
            ],
            'AccountAdmin' => [
                'resourceType' => Issues\Issue10\Account::class,
                'actions' => Actions::all()
            ],
            'BackendAdmin' => [
                'resource' => new VirtualResource('backend'),
                'actions' => new Actions([Actions::VIEW]),
            ],
        ];

        $setup->registerRoles($roles, $this->acl);

        $setup->setActionsClass(Model\Actions::class);

        return $setup;
    }

    /**
     * Look into environment variables (defined in phpunit.xml configuration files).
     * @return array
     */
    private function getDBParams()
    {
        $dbParams = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        if (isset($GLOBALS['db_type'])) {
            $dbParams['driver'] = $GLOBALS['db_type'];
        }
        if (isset($GLOBALS['db_username'])) {
            $dbParams['user'] = $GLOBALS['db_username'];
        }
        if (isset($GLOBALS['db_password'])) {
            $dbParams['password'] = $GLOBALS['db_password'];
        }
        if (isset($GLOBALS['db_name'])) {
            $dbParams['dbname'] = $GLOBALS['db_name'];
        }

        return $dbParams;
    }

    private function dropAndCreateDB(Connection $connection)
    {
        // Drop and recreate the database
        if ($connection->getDatabasePlatform()->supportsCreateDropDatabase()) {
            $dbname = $connection->getDatabase();
            $connection->close();

            $connection->getSchemaManager()->dropAndCreateDatabase($dbname);

            $connection->connect();
        } else {
            $sm = $connection->getSchemaManager();

            /* @var $schema Schema */
            $schema = $sm->createSchema();
            $stmts = $schema->toDropSql($connection->getDatabasePlatform());

            foreach ($stmts as $stmt) {
                $connection->exec($stmt);
            }
        }
    }
}
