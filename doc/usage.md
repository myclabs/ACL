---
currentMenu: usage
---

# Usage

## Restrict access to resources

The first thing to do is to create some access rules.

For example, let's say you are creating a blog engine and you want to define who can access the articles.
You'll have the following objects:

- users accounts are the security identities
- articles are the resources to which you want to restrict access
- there will be several roles, like an "article editor", an administrator, …
- each role will be able to do specific actions on the articles (edit, delete, …)


### 1. Define the security identity

As we said, users are the security identities (they could also be customers, clients, usergroups, profiles, accounts …).

Here is an example of a simple user class that implements the `SecurityIdentityInterface`:

```php
/**
 * @ORM\Entity
 */
class User implements SecurityIdentityInterface
{
    use SecurityIdentityTrait;

    /**
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var RoleEntry[]
     * @ORM\OneToMany(targetEntity="MyCLabs\ACL\Model\RoleEntry", mappedBy="securityIdentity",
     *     cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $roleEntries;

    public function __construct()
    {
        $this->roleEntries = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }
}
```

As you can see we used the `SecurityIdentityTrait` to implement methods required by the interface, but we still
need to declare the `$roleEntries` association to map it with Doctrine.


### 2. Mark an entity as a resource

If you want to restrict access to an entity (e.g. the article #42), you need to make it
implement the `EntityResource` interface:

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

You can also optionally add an association to the roles that apply on this resource.
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

Here the association targets `ArticleEditorRole`, but if you have several roles that apply to articles, you
might want to have an abstract `BaseArticleRole` that you can reference in your Doctrine association.


### 3. Create a new role

The role gives the authorizations.

They are declared in an array and must be registered with the ACLSetup class:

```php
$roles = [];
$roles = ['articleEditor'] = [
    'resource' => 'My\Model\Article',
    'actions' => new Actions([Actions::VIEW, Actions::EDIT])
];

$aclSetup->registerRoles($roles);
```

The authorizations given by the role are created in the `createAuthorizations()` method.

For each role you need to specify:

- *the role name*
- *the resource* (here the article)
- *the actions* that can be performed on the resource

The resource can be either a class name (as shown above) or an class resource, which will
give access to all entities of that type:

```php
$roles = ['allArticlesEditor'] = [
    'resource' => new ClassResource('Tests\MyCLabs\ACL\Integration\Model\Article'),
    'actions' => new Actions([Actions::VIEW, Actions::EDIT])
];
```

Authorizations that are granted on class resources (i.e. *all entities of that class*) are cascaded
automatically to each sub-resource (i.e. all entities). Read the documentation about
[authorization cascading](cascading.md) to learn more.

Another common use case for class resources are object creation. For example, you want an article editor
to be able to create new articles. You can do this in the `ArticleEditorRole`:

```php
$roles = ['articleCreator'] = [
    'resource' => new ClassResource('My\Model\Article'),
    'actions' => new Actions([Actions::CREATE])
];
```


### 4. Grant/revoke roles to users

Now that everything is defined, we can grant users some roles very simply:

```php
// On a single resource
$acl->grant($user, 'ArticleEditor', $article);
// On a class resource
$acl->grant($user, 'AllArticlesEditor');
```

Here, the ACL will add the role to the user and use the role to automatically generate and persist the
related authorizations.

To revoke a role to an user, use the revoke method:
```php
// For a single resource
$acl->revoke($user, 'ArticleEditor', $article);
// For a class resource
$acl->revoke($user, 'AllArticlesEditor');
```

## Check access

Now that accesses are defined, we can test the authorizations:

```php
$acl->grant($user, 'ArticleEditor', $article);

echo $acl->isAllowed($user, Actions::VIEW, $article);   // true
echo $acl->isAllowed($user, Actions::EDIT, $article);   // true
echo $acl->isAllowed($user, Actions::DELETE, $article); // false

// If you added the CREATE authorization on the class resource:
$allArticles = new ClassResource('My\Model\Article');
echo $acl->isAllowed($user, Actions::CREATE, $allArticles); // true
```

**Note:** You should never test if a user has a role to check access. This practice, called "implicit" access control,
makes your access rules hardcoded and very likely to fail or break on change. Instead, it is recommended that
you use "explicit" access control using authorizations on resources. Read more about this in
[this excellent article about RBAC](https://stormpath.com/blog/new-rbac-resource-based-access-control/).
This is in part for that reason that testing if a user has a role is not that practical with this library.
It's not a main feature, because it shouldn't be used a lot. Use `isAllowed()` instead.


### Filter at query level

If you fetch entities from the database and then test `isAllowed()` onto each entity, this is inefficient:

- you will load all the entities, even the one the user can't access
- you will issue one query for each `isAllowed()` call

There is a much more efficient solution: filtering entities at the query level (i.e. SQL level).

For this, move on to the [Filtering queries documentation](filtering-queries.md).
