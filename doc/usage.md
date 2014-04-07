---
menu: usage
---

# Usage

## 1. Mark your entity as a resource

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

## 2. Creating a new role

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
