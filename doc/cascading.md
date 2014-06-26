---
currentMenu: cascading
---

# Authorization cascading

There are 2 ways to cascade authorizations:

- via a hierarchy of resource: parent resources cascade their authorizations to sub-resources
- via a custom cascading strategy (if you have exotic needs)

The first solution is supported out of the box. Example: allowing a user to access a folder and all its sub-folders.

You have 2 solutions to define the hierarchical structure:

- implementing the `CascadingResource` interface
- writing a `ResourceGraphTraverser`

## CascadingResource

This is a very simple solution, yet a bit limited, and it crowds your entity a bit.

Example:

```php
class Category implements EntityResource, CascadingResource
{
    /**
     * @var Category[] Sub-categories
     **/
    private $children;

    /**
     * @var Category|null Parent category
     **/
    private $parent;

    // ...

    public function getParentResources(EntityManager $entityManager)
    {
        $parents = [ new ClassResource(get_class()) ];

        if ($this->parent !== null) {
            $parents[] = $this->parent;
        }

        return $parents;
    }

    public function getSubResources(EntityManager $entityManager)
    {
        return $this->children->toArray();
    }
}
```

Note: if you want to give authorizations on the class-resource "All categories" (`new ClassResource('Category')`)
don't forget to return it in `getParentResources()` (as shown above). Else you can ignore it.

Just so you know, `ClassResource` implements the `CascadingResource` interface:

```php
final class ClassResource implements ResourceInterface, CascadingResource
{
    // ...

    public function getSubResources(EntityManager $entityManager)
    {
        $repository = $entityManager->getRepository($this->class);

        return $repository->findAll();
    }
}
```

**Important**: with `CascadingResource`, MyCLabs\ACL will assume each resource only returns its direct
children/parent resources. So they will be traversed recursively, which sometimes can be inefficient.
Have a look below for an alternative solution.

## ResourceGraphTraverser

The `ResourceGraphTraverser` is an object you write that must return the parent and sub-resources of a resource.

As explained, it must return **all** the sub/parent resources, which avoids MyCLabs\ACL
recursively looking for sub/parent resources.

Example:

```php
class FolderResourceGraphTraverser implements ResourceGraphTraverser
{
    public function getAllParentResources(ResourceInterface $resource)
    {
        if (! $resource instanceof Folder) {
            throw new \RuntimeException;
        }

        $parents = $resource->getAllParentFoldersRecursively();
        $parents[] = new ClassResource(Folder::class);

        return $parents;
    }

    public function getAllSubResources(ResourceInterface $resource)
    {
        if (! $resource instanceof Folder) {
            throw new \RuntimeException;
        }

        return array_merge(
            $resource->getAllSubFoldersRecursively(),
            $resource->getAllFiles()
        );
    }
}
```

To register it:

```php
$cascadeStrategy = new SimpleCascadeStrategy($entityManager);
$cascadeStrategy->setResourceGraphTraverser(
    Folder::class,
    $c->get(FolderResourceGraphTraverser::class)
);

$acl = new ACL($em, $cascadeStrategy);
```
