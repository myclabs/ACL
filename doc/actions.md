---
currentMenu: actions
---

# Actions

As you have seen in the examples, you can allow and test several actions on a resource.

- `$acl->allow($role, $actions, $resource)`

When allowing access, you can allow the user to do several actions like so:

```php
$actions = new Actions();
$actions->view = true;
$actions->edit = true;
```

Here is a shortcut to achieve the same result:

```php
$actions = new Actions([
    Actions::VIEW,
    Actions::EDIT,
]);

echo $actions->view; // true
echo $actions->delete; // false
```

- `$acl->isAllowed($user, $action, $resource)`

When testing access, you can only test for one action:

```php
$acl->isAllowed($user, Actions::EDIT, $resource);
```

That is the same as this, but using constant is obviously preferred:

```php
$acl->isAllowed($user, 'edit', $resource);
```


## Default actions

Here is the list of all actions natively supported:

```php
class Actions
{
    const VIEW = 'view';
    const CREATE = 'create';
    const EDIT = 'edit';
    const DELETE = 'delete';
    const UNDELETE = 'undelete';
    const ALLOW = 'allow';

    public $view = false;
    public $create = false;
    public $edit = false;
    public $delete = false;
    public $undelete = false;
    public $allow = false;

    // ....
}
```

You don't have to use them all if you don't need it.

FYI, `ALLOW` means "the user is allowed to allow other users on this resource", i.e. it's the action
of managing access on the resource. This is usually what an administrator does: he can configure the
accesses on the resources he administrates.


## Custom actions

You can add your own actions by overriding the `Actions` class:

```php
namespace My\Model;

use Doctrine\ORM\Mapping as ORM;
use MyCLabs\ACL\Model\Actions as BaseActions;

/**
 * @ORM\Embeddable
 */
class Actions extends BaseActions
{
    const PUBLISH = 'publish';

    /**
     * @ORM\Column(type = "boolean")
     */
    public $publish = false;

    /**
     * {@inheritdoc}
     */
    public static function all()
    {
        return new static([
            static::VIEW,
            static::CREATE,
            static::EDIT,
            static::DELETE,
            static::UNDELETE,
            static::ALLOW,
            static::PUBLISH,
        ]);
    }
}
```

Here we added a "publish" action to restrict who can publish articles.

Now we need to configure MyCLabs\ACL to use this class instead of the base class:

```php
$aclSetup->setActionsClass('My\Model\Actions');
```
