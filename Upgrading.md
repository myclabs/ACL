# Upgrading

## 2.0

This is the upgrade guide from 1.x to 2.0.

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

### Cascading resources

The `CascadingResource` interface let's you define sub-resources and parent resources inside your model.
The signature of the methods defined by that interface have changed: they no longer get the entity manager.

Before:

```php
interface CascadingResource extends ResourceInterface
{
    public function getParentResources(EntityManager $entityManager);
    public function getSubResources(EntityManager $entityManager);
}
```

After:

```php
interface CascadingResource extends ResourceInterface
{
    public function getParentResources();
    public function getSubResources();
}
```

This is much cleaner because the model should never know about the entity manager. If you needed the entity
manager to get the parent or sub-resources, then you now need to write a `ResourceGraphTraverser`
(see the [Cascading](doc/cascading.md) documentation).
