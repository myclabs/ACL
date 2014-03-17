# MyCLabs ACL

## Introduction

MyCLabs ACL is a library that helps managing permissions on resources.

Vocabulary:

- Security identity: the entity which will be granted some access (this is generally the user)
- Resource: a *thing* to which we want to control the access
- Authorization: allows a security identity (user) to do something on a resource
- Role: a role gives authorizations to a user (e.g. an administrator, an article editor, a project owner, …)

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
     * @var ArticleEditorRole[]|Collection
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
        $aclManager->allow($this, new Actions([Actions::VIEW, Actions::EDIT]), $this->article);
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
$aclManager->allow($this, new Actions([Actions::VIEW]), new ClassResource('My\Model\Article'));
```

## Setup

TODO

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
