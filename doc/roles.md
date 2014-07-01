---
currentMenu: roles
---

The roles define which authorizations will be given to the user that is granted that role.

## Defining roles

```php
$roles = [
    // …
];

$acl = new ACL($entityManager, $roles);
```

Below are detailed the different kind of roles you can use.

### A role that applies to an entity resource

This is the most common role, it looks like this:

```php
$roles = [
    'ArticleEditor' => [
        'resourceType' => 'My\Model\Article',
        'actions'      => new Actions([ Actions::VIEW, Actions::EDIT ]),
    ],
];
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

### A role that applies to a class resource

These roles allow to do the actions on **all** the entities of the given class:

```php
$roles = [
    'AllArticlesEditor' => [
        'resource' => new ClassResource('My\Model\Article'),
        'actions'  => new Actions([ Actions::VIEW, Actions::EDIT ]),
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
        'actions'  => new Actions([ Actions::CREATE ]),
    ],
];

// We can test the authorization now:
echo $acl->isAllowed($user, Actions::CREATE, new ClassResource('My\Model\Article')); // true
```

### A role that has custom authorizations

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

As you can see, `authorizations` replaces `actions` in the array.


## Granting/revoking roles

You can grant roles to users very simply:

```php
// On a role that apply to an entity
$acl->grant($user, 'ArticleEditor', $article);

// On a role that apply to a whole class
$acl->grant($user, 'AllArticlesEditor');
```

Here, the ACL will add the role to the user and automatically insert the authorizations in database.

To revoke a role:

```php
$acl->revoke($user, 'ArticleEditor', $article);

$acl->revoke($user, 'AllArticlesEditor');
```

### Is granted?

To check if a user is granted some role:

```php
$acl->grant($user, 'ArticleEditor', $article);

echo $acl->isGranted($user, 'ArticleEditor', $article); // true
```

**Note:** You should usually never test if a user has a role to check access.

This practice, called *implicit access control*, makes your access rules hardcoded and very
likely to fail or break on change. Instead, it is recommended that
you use *explicit access control* using authorizations on resources.

Read more about this in
[this excellent article about RBAC](https://stormpath.com/blog/new-rbac-resource-based-access-control/).

In short, **prefer using `isAllowed()` instead of `isGranted()`**.
