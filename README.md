# MyCLabs ACL

## Introduction

You give permissions to a user by adding it a role:

```php
$user->addRole(new ArticleEditorRole($user, $article);
```

Test permissions:

```php
$aclManager->isAllowed($user, Actions::EDIT, $article);
```

### Pros

- extremely optimized:
  - filters queries at database level (you don't load entities the user can't access)
  - joins with only 1 extra table
- authorization cascading/inheritance
- you can use custom actions on top of the standards VIEW, EDIT, CREATE, DELETE, etc.
- authorizations are rebuildable: you can change what an "ArticleEditor" can do afterwards and just rebuild the ACL

### Cons

- some extra code: you need to write classes for authorizations and roles because of Doctrine limitations
- you can't authorize a user directly on a resource: you have to use roles (e.g. an Article Editor, or a Administrator)
- because of Doctrine limitations you need to flush you roles and resources before testing authorizations

## Usage

### 1. Creating a new resource

Let's say you want to control the access to an entity named `Article`.

You need to have your entity implement the `Resource` interface. Instead of implementing the methods
of the interface, you can use the `ResourceTrait`:

```php
class Article implements EntityResourceInterface
{
    use ResourceTrait;

    /**
     * @OneToMany(targetEntity="ArticleAuthorization", mappedBy="resource", fetch="EXTRA_LAZY")
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
    protected $resource;
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

    public function createAuthorizations()
    {
        return [
            ArticleAuthorization::create($this, new Actions([Actions::VIEW, Actions::EDIT], $this->article);
        ];
    }
}
```
