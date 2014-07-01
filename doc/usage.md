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


### 3. Define the roles

The role gives the authorizations. They are declared in an array that is passed to the constructor of the `ACL` class.

You can define several types of roles:

- roles that applies to an entity resource (e.g. *Project Manager* can give authorizations on a specific instance of the `Project` class)
- roles that applies to a class resource (e.g. *Administrator* can give authorizations on all the instances of the `Project` class)
- roles that define explicitly their authorizations, which allows to customize authorizations as you want

To keep the example simple, we will show a role that applies to an entity because this is the most common role:

```php
$roles = [
    'ArticleEditor' => [
        'resourceType' => 'My\Model\Article',
        'actions'      => new Actions([ Actions::VIEW, Actions::EDIT ]),
    ],
];

$acl = new ACL($entityManager, $roles);
```

It is pretty straightforward. Each time you grant the `ArticleEditor` role on an article, the user
will be allowed to VIEW and EDIT it.

Have a look at [the complete documentation about configuring role](roles.md).


### 4. Grant roles to users

Now that everything is defined, we can grant some roles very simply:

```php
// The user will be granted the role of editor on the given article
$acl->grant($user, 'ArticleEditor', $article);
```

Here, the ACL will add the role to the user and use the role to automatically generate and persist the
related authorizations.


## Check access

Now that accesses are defined, we can test the authorizations:

```php
$acl->grant($user, 'ArticleEditor', $article);

echo $acl->isAllowed($user, Actions::VIEW, $article);   // true
echo $acl->isAllowed($user, Actions::EDIT, $article);   // true
echo $acl->isAllowed($user, Actions::DELETE, $article); // false
```


### Filter at query level

If you fetch entities from the database and then test `isAllowed()` onto each entity, this is inefficient:

- you will load all the entities, even the one the user can't access
- you will issue one query for each `isAllowed()` call

There is a much more efficient solution: filtering entities at the query level (i.e. SQL level).

For this, move on to the [Filtering queries documentation](filtering-queries.md).
