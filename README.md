# MyCLabs ACL

[![Build Status](https://travis-ci.org/myclabs/ACL.png?branch=master)](https://travis-ci.org/myclabs/ACL) [![Coverage Status](https://coveralls.io/repos/myclabs/ACL/badge.png)](https://coveralls.io/r/myclabs/ACL) [![Latest Stable Version](https://poser.pugx.org/myclabs/acl/v/stable.png)](https://packagist.org/packages/myclabs/acl) [![Total Downloads](https://poser.pugx.org/myclabs/acl/downloads.png)](https://packagist.org/packages/myclabs/acl) [![License](https://poser.pugx.org/myclabs/acl/license.png)](https://packagist.org/packages/myclabs/acl)

MyCLabs ACL is a library that helps managing permissions on resources.

Vocabulary:

- **Security identity**: the entity which will be granted some access (this is generally the user)
- **Resource**: a *thing* to which we want to control the access
- **Authorization**: allows a security identity (user) to do something on a resource
- **Role**: a role gives authorizations to a user (e.g. an administrator, an article editor, a project owner, …)

There are 2 kinds of resources:

- an entity (example: article #123)
- all entities of a given type (example: all articles), which is represented by the classname of the entity

## Overview

You give permissions to a user by adding it a role:

```php
$aclManager->grant($user, new ArticleEditorRole($user, $article));
```

You remove permissions to a user by removing the role:

```php
$aclManager->unGrant($user, $role);
```

Test permissions:

```php
$aclManager->isAllowed($user, Actions::EDIT, $article);
```

You can also filter your queries to get only the entities the user has access to:

```php
$qb = $entityManager->createQueryBuilder();
$qb->select('article')->from('Model\Article', 'article');

QueryBuilderHelper::joinACL($qb, 'Model\Article', 'article', $user, Actions::EDIT);

// This query will return only the articles the user can edit
$articles = $qb->getQuery()->getResult();
```

### Features

- extremely optimized:
  - filters queries at database level (you don't load entities the user can't access)
  - joins with only 1 extra table
- authorization cascading/inheritance
- authorizations are rebuildable: you can change what an "ArticleEditor" can do afterwards and just rebuild the ACL
- supports your custom actions on top of standard actions like "view", "edit", "delete", …

### Limitations

- because of Doctrine limitations you need to flush your resources before giving or testing authorizations
- backed up by the database: testing `isAllowed` means one call to the database

## Usage

### 1. Mark your entity as a resource

Let's say you want to control the access to an entity named `Article`.

You need to have your entity implement the `EntityResource` interface:

```php
class Article implements EntityResource
{
    // ...

    public function getId()
    {
        return $this->id;
    }
}
```

You can also add an association to the roles that apply on this resource.
Such association is very useful so that the roles (and their authorizations) are deleted in cascade
when the resource is deleted:

```php
class Article implements EntityResource
{
    // ...

    /**
     * @ORM\OneToMany(targetEntity="ArticleEditorRole", mappedBy="article", cascade={"remove"})
     */
    protected $roles;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
    }
}
```

This association can also be useful if you need to find all the "editors" of an article for example.

### 2. Creating a new role

The role gives the authorizations.

To create a new role, extend the `Role` abstract class:

```php
/**
 * @Entity(readOnly=true)
 */
class ArticleEditorRole extends Role
{
    /**
     * @ManyToOne(targetEntity="Article", inversedBy="roles")
     */
    protected $article;

    public function __construct(User $user, Article $article)
    {
        $this->article = $article;

        parent::__construct($user);
    }

    public function createAuthorizations(ACLManager $aclManager)
    {
        $aclManager->allow(
            $this,
            new Actions([Actions::VIEW, Actions::EDIT]),
            $this->article
        );
    }
}
```

The authorizations given by the role are created in the `createAuthorizations()` method.

For creating an authorization, you need to call `$aclManager->allow()` with:

- the role (which will also provide the user/security identity that is being given access)
- the actions that are included in the authorization
- the resource

The resource can be either an entity instance (as shown above) or an entity classname, which will
give access to all entities of that type:

```php
// This will allow the users having the role to be able to view ALL the articles
$aclManager->allow(
    $this,
    new Actions([Actions::VIEW]),
    new ClassResource('My\Model\Article')
);
```

## Setup

WIP

You first need to register the annotation mapping to your Doctrine metadata driver.

Then you can configure the ACLManager:

```php
$aclManager = new ACLManager($entityManager);

$evm = $entityManager->getEventManager();

// Configure which entity implements the SecurityIdentityInterface
$rtel = new ResolveTargetEntityListener();
$rtel->addResolveTargetEntity('MyCLabs\ACL\Model\SecurityIdentityInterface', 'My\Model\User', []);
$evm->addEventListener(Events::loadClassMetadata, $rtel);

// Register the roles
$metadataLoader = new MetadataLoader();
$metadataLoader->registerRoleClass('My\Model\ArticleEditorRole', 'articleEditor');
$evm->addEventListener(Events::loadClassMetadata, $metadataLoader);

// Register the listener that looks for new resources
$aclManagerLocator = function () use ($aclManager) {
    return $aclManager;
};
$evm->addEventSubscriber(new EntityManagerListener($aclManagerLocator));
```

## Authorization cascade

WIP

## Custom actions

The default actions that you can use are:

- view
- edit
- delete
- undelete
- allow (= manage permissions on the resource)
- create

You can add your own actions by overriding the `Actions` class:

```php
namespace My\Model;

use Doctrine\ORM\Mapping as ORM;
use MyCLabs\ACL\Model\Actions as BaseActions;

/**
 * @ORM\Embeddable
 */
class Actions extends BaseActions
{
    const PUBLISH = 'publish';

    /**
     * @ORM\Column(type = "boolean")
     */
    public $publish = false;

    /**
     * {@inheritdoc}
     */
    public static function all()
    {
        return new static([
            static::VIEW,
            static::CREATE,
            static::EDIT,
            static::DELETE,
            static::UNDELETE,
            static::ALLOW,
            static::PUBLISH,
        ]);
    }
}
```

Here we added a "publish" action to restrict who can publish articles.
Now we need to configure MyCLabs\ACL to use this class instead of the base class:

```php
$metadataLoader->registerActionsClass('My\Model\Actions');
```

## Performances

To ensure the best performances, you need to make sure of the followings:

- Roles should be set as "Read Only" for Doctrine so that they are not tracked for changes uselessly

This is minor, but why not.
This is not a hard requirement though, if your roles can change, you are free to ignore this.

Example with annotations:

```php
/**
 * @Entity(readOnly=true)
 */
class ArticleEditorRole extends Role
{
}
```

Or with YAML:

```yaml
Namespace\ArticleEditorRole:
  type: entity
  readOnly: true
```
