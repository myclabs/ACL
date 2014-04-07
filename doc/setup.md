---
menu: setup
---

# Setup

## Installation

Install the library with Composer:

```json
{
    "require": {
        "myclabs/acl": "*"
    }
}
```

## Configuration

You first need to register the annotation mapping to your Doctrine metadata driver.
Here is for example how you would do it with the most basic Doctrine configuration:

```php
$paths = [
    // Your model classes
    __DIR__ . '/../src/My/Model',

    // MyCLabs ACL model classes (adjust the directory)
    __DIR__ . '/../vendor/myclabs/acl/src/Model',
];

// Doctrine configuration
$config = Setup::createAnnotationMetadataConfiguration($paths);
$em = EntityManager::create($dbParams, $config);
```

Creating the ACL object is simple:

```php
$acl = new ACL($entityManager);
```

However, you must separately register some listeners on the entity manager.
The `ACLSetup` class is here to help you:

```php
$aclSetup = new \MyCLabs\ACL\Doctrine\ACLSetup();
// Set which class implements the SecurityIdentityInterface (must be called once)
$aclSetup->setSecurityIdentityClass('My\Model\User')
// Register role classes
$aclSetup->registerRoleClass('My\Model\ArticleEditorRole', 'articleEditor');

// To avoid instantiating the ACL uselessly (and avoid a circular dependency),
// we must use a "locator" callback
$aclLocator = function () {
    // If you don't use a container, just return the $acl variable
    return $container->get('MyCLabs\ACL\ACL');
};

// Apply the configuration to the entity manager
$aclSetup->setUpEntityManager($entityManager, $aclLocator);
```

These listeners handle different things, like registering your role and user classes, and registering
a listener that will act when new resources/entities are created (to cascade authorizations).
