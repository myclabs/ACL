# Upgrading

## 2.0

This is the upgrade guide from 1.x to 2.0.

### Entities

You need to remove the `$roles` collection that you may have added to your entities (resources):

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
