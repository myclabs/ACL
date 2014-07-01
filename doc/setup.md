---
currentMenu: setup
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

### 1. Doctrine mapping

You first need to add the mapping of `MyCLabs\ACL\Model` classes to your Doctrine configuration.

Here is for example how you would do it with the most basic Doctrine setup:

```php
$paths = [
    // Your model classes
    __DIR__ . '/../src/My/Model',

    // MyCLabs ACL model classes (adjust the directory)
    __DIR__ . '/../vendor/myclabs/acl/src/Model',
];

$config = Setup::createConfiguration($isDevMode);
// Myclabs/ACL uses namespaces annotations
$config->setMetadataDriverImpl($config->newDefaultAnnotationDriver($paths, false));

$em = EntityManager::create($dbParams, $config);
```

### 2. Create the ACL object

Creating the ACL object is simple:

```php
$roles = [
    // we will define roles here later
];

$acl = new ACL($entityManager, $roles);
```

You'll need to define roles, and also define an Identity class. This is shown in the next section: [Usage](usage.md).

### 3. Set up Doctrine listeners

Then, you must separately register some listeners on the entity manager.
The `ACLSetup` class is here to help you:

```php
$aclSetup = new ACLSetup();

// Set which class implements the Identity interface (must be called once)
$aclSetup->setIdentityClass('My\Model\User');

// Apply the configuration to the entity manager
$aclSetup->setUpEntityManager($entityManager, function () use ($acl) { return $acl; });
```

These listeners handle different things, like registering your user classes, and registering
a listener that will act when new resources/entities are created (to cascade authorizations).

## Using a DI container

You can also use a container to avoid instantiating the ACL automatically when the entity manager is created
(and avoid a circular dependency):

```php
$aclLocator = function () {
    return $container->get('MyCLabs\ACL\ACL');
};

$aclSetup->setUpEntityManager($entityManager, $aclLocator);
```

This is not required, don't worry if you don't understand that part, you can skip it.

## Cascade delete

To be as efficient as possible, MyCLabs\ACL uses `ON DELETE CASCADE` at database level.

For example, when a role is revoked, all of its authorizations will be deleted in cascade by MySQL/SQLite/…
That allows to bypass using Doctrine's "cascade remove" which loads all the entities in memory (there could
be thousands of authorizations).

However this means **your database must support CASCADE operations**. MySQL and PostgreSQL support it,
but SQLite usually [needs a configuration step](http://www.sqlite.org/foreignkeys.html#fk_enable):

```php
$entityManager->getConnection()->executeQuery('PRAGMA foreign_keys = ON');
```
