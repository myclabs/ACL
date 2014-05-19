---
menu: performances
---

# Performances

For better performances, you can follow the following advices:

- Cache calls to `isAllowed()`: every time you call that method, it will issue a query to the database.

You shouldn't call `isAllowed()` a lot, if you do try instead to filter your queries using the ACL (see above),
this is much more efficient. However, if you do, you might want to cache the results of those calls in order
to avoid doing too many queries.

MyCLabs\ACL doesn't ship with a cache for now mainly because of cache invalidation when ACLs changes.
However this can change, you are free to add an issue about it.

Be aware that using a cache for this is not mandatory. If your application doesn't handle a lot of traffic
the ACL system will work just fine (the isAllowed query is very simple and optimized).
