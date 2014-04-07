# MyCLabs ACL

[![Build Status](https://travis-ci.org/myclabs/ACL.png?branch=master)](https://travis-ci.org/myclabs/ACL) [![Coverage Status](https://coveralls.io/repos/myclabs/ACL/badge.png)](https://coveralls.io/r/myclabs/ACL) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/myclabs/ACL/badges/quality-score.png?s=2997ec4cb570c1cfef520d541daac853527d173e)](https://scrutinizer-ci.com/g/myclabs/ACL/) [![Latest Stable Version](https://poser.pugx.org/myclabs/acl/v/stable.png)](https://packagist.org/packages/myclabs/acl) [![Total Downloads](https://poser.pugx.org/myclabs/acl/downloads.png)](https://packagist.org/packages/myclabs/acl) [![License](https://poser.pugx.org/myclabs/acl/license.png)](https://packagist.org/packages/myclabs/acl)

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
$acl->grant($user, new ArticleEditorRole($user, $article));
```

You remove permissions to a user by removing the role:

```php
$acl->unGrant($user, $role);
```

Test permissions:

```php
$acl->isAllowed($user, Actions::EDIT, $article);
```

You can also filter your queries to get only the entities the user has access to:

```php
$qb = $entityManager->createQueryBuilder();
$qb->select('article')->from('Model\Article', 'article');

ACLQueryHelper::joinACL($qb, $user, Actions::EDIT);

// This query will return only the articles the user can edit
$articles = $qb->getQuery()->getResult();
```

### Features

- stored in database (you don't need to handle persistence yourself)
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

    public function createAuthorizations(ACL $acl)
    {
        $acl->allow(
            $this,
            new Actions([Actions::VIEW, Actions::EDIT]),
            $this->article
        );
    }
}
```

The authorizations given by the role are created in the `createAuthorizations()` method.

For creating an authorization, you need to call `$acl->allow()` with:

- the role (which will also provide the user/security identity that is being given access)
- the actions that are included in the authorization
- the resource

The resource can be either an entity instance (as shown above) or an entity classname, which will
give access to all entities of that type:

```php
// This will allow the users having the role to be able to view ALL the articles
$acl->allow(
    $this,
    new Actions([Actions::VIEW]),
    new ClassResource('My\Model\Article')
);
```

### Actions

As you have seen in the previous examples, you can allow and test several actions on a resource.

- `allow`

When allowing access, you can allow the user to do several actions like so:

```php
$actions = new Actions();
$actions->view = true;
$actions->edit = true;

$acl->allow($role, $actions, $resource);
```

The way shown in the examples above is a shortcut, sometimes more practical:

```php
$actions = new Actions([
    Actions::VIEW,
    Actions::EDIT,
]);

echo $actions->view; // true
echo $actions->delete; // false
```

- `isAllowed`

When testing access, you can only test for one action:

```php
$acl->isAllowed($user, Actions::EDIT, $resource);
```

Here is the list of all actions natively supported:

```php
class Actions
{
    const VIEW = 'view';
    const CREATE = 'create';
    const EDIT = 'edit';
    const DELETE = 'delete';
    const UNDELETE = 'undelete';
    const ALLOW = 'allow';

    public $view = false;
    public $create = false;
    public $edit = false;
    public $delete = false;
    public $undelete = false;
    public $allow = false;

    // ....
}
```

You don't have to use them all if you don't need it.

FYI, `ALLOW` means "the user is allowed to allow other users on this resource", i.e. it's the action
of managing access on the resource. This is usually what an administrator does: he can configure the
accesses on the resources he administrates.


## Setup

You first need to register the annotation mapping to your Doctrine metadata driver.

Creating the ACL is simple:

```php
$acl = new ACL($entityManager);
```

However, you must register some listener on the entity manager:

```php
$aclSetup = new \MyCLabs\ACL\Doctrine\ACLSetup();
// Set which class implements the SecurityIdentityInterface (must be called once)
$aclSetup->setSecurityIdentityClass('My\Model\User')
// Register role classes
$aclSetup->registerRoleClass('My\Model\ArticleEditorRole', 'articleEditor');

// To avoid instantiating the ACL uselessly (and avoid a circular dependency),
// we must use a "locator" callback
$aclLocator = function () {
    return $container->get('MyCLabs\ACL\ACL');
};

// Apply the configuration to the entity manager
$aclSetup->setUpEntityManager($entityManager, $aclLocator);
```

## Authorization cascading

There are 2 ways to cascade authorizations:

- via a hierarchy of resource: parent resources cascade their authorizations to sub-resources
- via a custom cascading strategy (if you have exotic needs)

The first solution is supported out of the box. Example: allowing a user to access a folder and all its sub-folders.

You have 2 solutions to define the hierarchical structure:

- implementing the `CascadingResource` interface
- writing a `ResourceGraphTraverser`

### CascadingResource

This is a very simple solution, yet a bit limited, and it crowds your entity a bit.

Example:

```php
class Category implements EntityResource, CascadingResource
{
    /**
     * @var Category[] Sub-categories
     **/
    private $children;

    /**
     * @var Category|null Parent category
     **/
    private $parent;

    // ...

    public function getParentResources(EntityManager $entityManager)
    {
        $parents = [ new ClassResource(get_class()) ];

        if ($this->parent !== null) {
            $parents[] = $this->parent;
        }

        return $parents;
    }

    public function getSubResources(EntityManager $entityManager)
    {
        return $this->children->toArray();
    }
}
```

Note: if you want to give authorizations on the class-resource "All categories" (`new ClassResource('Category')`)
don't forget to return it in `getParentResources()` (as shown above). Else you can ignore it.

Just so you know, `ClassResource` implements the `CascadingResource` interface:

```php
final class ClassResource implements ResourceInterface, CascadingResource
{
    // ...

    public function getSubResources(EntityManager $entityManager)
    {
        $repository = $entityManager->getRepository($this->class);

        return $repository->findAll();
    }
}
```

**Important**: with `CascadingResource`, MyCLabs\ACL will assume each resource only returns its direct
children/parent resources. So they will be traversed recursively, which sometimes can be inefficient.
Have a look below for an alternative solution.

### ResourceGraphTraverser

The `ResourceGraphTraverser` is an object you write that must return the parent and sub-resources of a resource.

As explained, it must return **all** the sub/parent resources, which avoids MyCLabs\ACL
recursively looking for sub/parent resources.

Example:

```php
class FolderResourceGraphTraverser implements ResourceGraphTraverser
{
    public function getAllParentResources(ResourceInterface $resource)
    {
        if (! $resource instanceof Folder) {
            throw new \RuntimeException;
        }

        $parents = $resource->getAllParentFoldersRecursively();
        $parents[] = new ClassResource(Folder::class);

        return $parents;
    }

    public function getAllSubResources(ResourceInterface $resource)
    {
        if (! $resource instanceof Folder) {
            throw new \RuntimeException;
        }

        return array_merge(
            $resource->getAllSubFoldersRecursively(),
            $resource->getAllFiles()
        );
    }
}
```

To register it:

```php
$cascadeStrategy = new SimpleCascadeStrategy($entityManager);
$cascadeStrategy->setResourceGraphTraverser(
    Folder::class,
    $c->get(FolderResourceGraphTraverser::class)
);

$acl = new ACL($em, $cascadeStrategy);
```

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
$aclSetup->setActionsClass('My\Model\Actions');
```

## Performances

For better performances, you can follow the following advices:

- Cache calls to `isAllowed()`: every time you call that method, it will issue a query to the database.

You shouldn't call `isAllowed()` a lot, if you do try instead to filter your queries using the ACL (see above),
this is much more efficient. However, if you do, you might want to cache the results of those calls in order
to avoid doing too many queries.

MyCLabs\ACL doesn't ship with a cache for now because there are some problems associated to it, mainly
cache invalidation when ACLs changes. However this can change, you are free to file an issue for this.

Be aware that using a cache for this is not mandatory. If your application doesn't handle a lot of traffic
the ACL system will work just fine (the isAllowed query is very simple and optimized).

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
