# MyCLabs ACL

## Introduction

You give permissions to a user by adding it a role:

```php
$aclManager->grant($user, new ArticleEditorRole($user, $article));
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

- [X] entity (example: article #123)
- [X] entity class (example: all articles)
- [ ] entity field (example: comments of article #123)
- [ ] entity class field (example: comments of all articles)

### Cons

- some extra code: you need to write classes for authorizations and roles because of Doctrine limitations
- you can't authorize a user directly on a resource: you have to use roles (e.g. an Article Editor, or a Administrator)
- because of Doctrine limitations you need to flush your resources before giving or testing authorizations

## Usage

### 1. Creating a new resource

Let's say you want to control the access to an entity named `Article`.

You need to have your entity implement the `EntityResourceInterface` interface and map some fields:

```php
class Article implements EntityResourceInterface
{
    /**
     * @OneToMany(targetEntity="ArticleAuthorization", mappedBy="entity", fetch="EXTRA_LAZY")
     */
    protected $authorizations;

    public function __construct()
    {
        $this->authorizations = new ArrayCollection();
    }
}
```

To create authorizations on the new resource, you need to create a new kind of authorization:

```php
/**
 * @Entity(readOnly=true)
 */
class ArticleAuthorization extends Authorization
{
    /**
     * @ManyToOne(targetEntity="Article", inversedBy="authorizations")
     * @JoinColumn(onDelete="CASCADE")
     */
    protected $entity;
}
```


### 2. Creating a new role

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
}
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
