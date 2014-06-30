---
currentMenu: usage
---

# Usage

## Restrict access to resources

The first thing to do is to create some access rules.

For example, let's say you are creating a blog engine and you want to define who can access the articles.
You'll have the following objects:

- users accounts are the identities
- articles are the resources to which you want to restrict access
- there will be several roles, like an "article editor", an administrator, …
- each role will be able to do specific actions on the articles (edit, delete, …)


### 1. Define the identity

As we said, users are the identities (they could also be customers, clients, usergroups, profiles, accounts …).

Here is an example of a simple user class that implements the `Identity` interface:

```php
/**
 * @ORM\Entity
 */
class User implements Identity
{
    use IdentityTrait;

    /**
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var RoleEntry[]
     * @ORM\OneToMany(targetEntity="MyCLabs\ACL\Model\RoleEntry", mappedBy="identity",
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

As you can see we used the `IdentityTrait` to implement methods required by the interface, but we still
need to declare the `$roleEntries` association to map it with Doctrine.


### 2. Mark an entity as a resource

If you want to restrict access to an entity (e.g. the article #42), you need to make it
implement the `ResourceInterface` interface. You can then either implement `getResourceId()` manually
or use the `EntityResourceTrait` that will do it for you (as long as you have a `getId()` method).

```php
class Article implements ResourceInterface
{
    use EntityResourceTrait;

    // ...

    public function getId()
    {
        return $this->id;
    }
}
```


### 3. Create a new role

The role gives the authorizations. They are declared in an array and must be registered with the `ACLSetup` class.

You can define several types of roles:

#### A role that applies to an entity resource

This is the most common role, it looks like this:

```php
$roles = [
    'ArticleEditor' => [
        'resourceType' => 'My\Model\Article',
        'actions'      => [ Actions::VIEW, Actions::EDIT ],
    ],
];

$aclSetup->registerRoles($roles);
```

It is pretty straightforward. Each time you grant the `ArticleEditor` role on an article, the user
will be allowed to VIEW and EDIT it.

The `resourceType` configuration allows to restrict the type of the resource this role will apply to.
It can be ignored, but it is recommended to configure it explicitely to avoid mistakes later
(for example attributing the ArticleEditor role on a completely different object).

To grant this role, you will need to pass a resource of the correct class:

```php
$acl->grant($user, 'ArticleEditor', $article);
```

#### A role that applies to a class resource

These roles allow to do the actions on **all** the entities of the given class:

```php
$roles = [
    'AllArticlesEditor' => [
        'resource' => new ClassResource('My\Model\Article'),
        'actions'  => [ Actions::VIEW, Actions::EDIT ],
    ],
];
```

To grant this roles, you don't need to pass a resource:

```php
$acl->grant($user, 'AllArticlesEditor');
```

Authorizations that are granted on class resources (i.e. *all entities of that class*) are cascaded
automatically to each sub-resource (i.e. all entities). Read the documentation about
[authorization cascading](cascading.md) to learn more.

Another common use case for class resources are object creation. For example, you want an article creator
to be able to create new articles:

```php
$roles = [
    'ArticleCreator' => [
        'resource' => new ClassResource('My\Model\Article'),
        'actions'  => [ Actions::CREATE ],
    ],
];
```

#### A role that has custom authorizations

You can also configure freely how the authorizations are created with a closure, for example:

```php
$roles = [
    'Administrator' => [
        'resource' => new ClassResource('My\Model\Article'),
        'authorizations' => function (ACL $acl, RoleEntry $roleEntry, ResourceInterface $resource) {
            // Allows to do everything on all the articles
            $acl->allow($roleEntry, Actions::all(), $resource);

            // Allows to create new articles
            $acl->allow($roleEntry, new Actions([ Actions::CREATE ]), $resource);
        },
    ],
];
```

As you might have guessed, `authorizations` replaces `actions` in the array.


### 4. Grant/revoke roles to users

Now that everything is defined, we can grant users some roles very simply:

```php
// On an entity resource
$acl->grant($user, 'ArticleEditor', $article);

// On a class resource
$acl->grant($user, 'AllArticlesEditor');
```

Here, the ACL will add the role to the user and use the role to automatically generate and persist the
related authorizations.

To revoke a role to an user, use the revoke method:

```php
// For an entity resource
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
