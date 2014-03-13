# MyCLabs ACL

## Introduction

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
- you can use custom actions on top of the standards VIEW, EDIT, CREATE, DELETE, etc.
- authorizations are rebuildable: you can change what an "ArticleEditor" can do afterwards and just rebuild the ACL

Scopes of access available:

- entity (example: article #123)
- entity class (example: all articles)
- entity field (example: comments of article #123)
- entity class field (example: comments of all articles)

### Cons

- you can't authorize a user directly on a resource: you have to use roles (e.g. an Article Editor, or a Administrator)
- because of Doctrine limitations you need to flush your resources before giving or testing authorizations

## Usage

### 1. Mark your entity as a resource

Let's say you want to control the access to an entity named `Article`.

You need to have your entity implement the `EntityResourceInterface` interface:

```php
class Article implements EntityResourceInterface
{
    // ...

    public function getId()
    {
        return $this->id;
    }
}
```

### 2. Creating a new role

The role will create the authorizations.

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

    public function createAuthorizations(EntityManager $entityManager)
    {
        $authorization = Authorization::create(
            $this,
            new Actions([Actions::VIEW, Actions::EDIT]),
            Resource::fromEntity($this->article)
        );

        return [ $authorization ];
    }
}
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

## Customizing

MyCLabs\ACL was built to give you as much liberty as possible.
Here is a non exhaustive list of things you can do but are not described in the documentation:

- You can add a reverse association from an entity (resource) to its role.

That can be useful if you need to fetch all the "editors" of article X for example.
