---
menu: filtering
---

# Filtering queries

Filtering queries is pretty straightforward thanks to the simple architecture behind all this:
there is a single `ACL_Authorization` table mapped to the `Authorization` Doctrine entity.

Filtering in a SQL query would then look like this:

```sql
SELECT article.* FROM Blog_Article article
INNER JOIN ACL_Authorization authorization
    ON authorization.entity_id = article.id
    AND authorization.entity_class = 'Blog\\Article'
WHERE authorization.securityIdentity_id = :userId
    AND actions_edit = true
```

## Doctrine queries

Of course what is really interesting is to filter in our **Doctrine queries**, and for this the
`ACLQueryHelper` makes it very simple:

```php
$qb = $entityManager->createQueryBuilder();

$qb->select('article')
   ->from('Blog\Article', 'article');

ACLQueryHelper::joinACL($qb, $user, Actions::EDIT);

// This query will return only the articles the user can edit
$articles = $qb->getQuery()->getResult();
```

This will generate the following DQL query:

```sql
SELECT article FROM Blog\Article article
INNER JOIN MyCLabs\ACL\Model\Authorization authorization
    WITH authorization.entityId = article.id
WHERE authorization.entityClass = 'Blog\\Article'
    AND authorization.securityIdentity = :user
    AND authorization.actions.edit = true
```

You can of course combine `ACLQueryHelper::joinACL()` with additional filtering on the query builder.
Unless you are writing some weird queries, the filtering should work as expected since it's a simple
JOIN and WHERE.