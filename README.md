# MyCLabs ACL

## Introduction

You give permissions to a user by adding it a role:

```php
$aclService->addRole($user, new CellAdminRole($user, $cell));
```

Test permissions:

```php
$aclService->isAllowed($user, Actions::EDIT, $resource);
```

## Usage

### 1. Creating a new resource

Let's say you want to control the access to an entity named `Article`.

You need to have your entity implement the `Resource` interface. Instead of implementing the methods
of the interface, you can use the `ResourceTrait`:

```php
class Article implements ResourceInterface
{
    use ResourceTrait;

    /**
     * @var ArticleAuthorization[]|Collection
     */
    protected $authorizations;

    public function __construct()
    {
        $this->authorizations = new ArrayCollection();
    }
}
```

Here is how you would map it with Doctrine:

```yaml
MyApp\Domain\Article:
  type: entity

  oneToMany:
    authorizations:
      targetEntity: MyApp\Domain\ACL\ArticleAuthorization
      mappedBy: resource
```

To create authorizations on the new resource, you need to create a new kind of authorization:

```php
class ArticleAuthorization extends Authorization
{
}
```

and map its `resource` field:

```yaml
MyApp\Domain\ACL\ArticleAuthorization:
  type: entity

  manyToOne:
    resource:
      targetEntity: MyApp\Domain\Article
      inversedBy: authorizations
      joinColumn:
        nullable: false
        onDelete: CASCADE
```


### 2. Creating a new role

To create a new role, extend the `Role` abstract class:

```php
class ArticleEditorRole extends Role
{
    protected $article;

    public function __construct(User $user, Article $article)
    {
        $this->article = $article;
        // Bidirectional associations is needed
        $article->addRole($this);

        parent::__construct($user);
    }

    public function createAuthorizations()
    {
        return [
            ArticleAuthorization::create($this, new Actions([Actions::VIEW, Actions::EDIT], $this->article);
        ];
    }
}
```
