# Upgrading

## 2.0

This is the upgrade guide from 1.x to 2.0.

### `ACL` class

- the deprecated method `unGrant()` has been removed and replaced by `revoke()`

### Roles

In 1.x, roles were defined as classes. Now they are defined in an array. You can read the "Roles" documentation
for upgrading.

### Identities

The `SecurityIdentityInterface` has been renamed to `Identity`. In the same vein, the trait has been
renamed to `IdentityTrait`. So you need to rename the interface and trait in your User class:

```php
class User implements Identity
{
    use IdentityTrait;

    // ...
}
```

### Entities

First, the interface `EntityResource` doesn't exist anymore. It has been unified with `ResourceInterface`
so that everything is simpler and more consistent.

What you can simply do is replace `EntityResource` with `ResourceInterface`, and use the
`EntityResourceTrait` to have the methods implemented.

You also need to remove the `$roles` collection that you may have added to your entities (resources):

```php
class Article implements EntityResource
{
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

This `$roles` collection is now obsolete and useless. Also, when resources are deleted, role entries will
be deleted in cascade by the ACL automatically.

Furthermore, if you need to find the role entries that apply to a resource, you can now use
`RoleEntryRepository::findByRoleAndResource($roleName, $resource)`.
