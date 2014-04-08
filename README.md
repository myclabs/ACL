---
menu: introduction
---

[![Build Status](https://travis-ci.org/myclabs/ACL.png?branch=master)](https://travis-ci.org/myclabs/ACL) [![Coverage Status](https://coveralls.io/repos/myclabs/ACL/badge.png)](https://coveralls.io/r/myclabs/ACL) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/myclabs/ACL/badges/quality-score.png?s=2997ec4cb570c1cfef520d541daac853527d173e)](https://scrutinizer-ci.com/g/myclabs/ACL/) [![Latest Stable Version](https://poser.pugx.org/myclabs/acl/v/stable.png)](https://packagist.org/packages/myclabs/acl) [![Total Downloads](https://poser.pugx.org/myclabs/acl/downloads.png)](https://packagist.org/packages/myclabs/acl) [![License](https://poser.pugx.org/myclabs/acl/license.png)](https://packagist.org/packages/myclabs/acl)

MyCLabs ACL is a library that helps managing permissions on resources.

Vocabulary:

- **Security identity**: the entity which will be granted some access (this is generally the user)
- **Resource**: a *thing* to which we want to control the access
- **Authorization**: allows a security identity (user) to do something on a resource
- **Role**: a role gives authorizations to a user (e.g. an administrator, an article editor, a project owner, …)

There are 2 kinds of resources:

- an entity (example: article #123)
- all entities of a given type (example: all articles), which is represented by the classname of the entity

## Overview

We hate being lost and confused, so everything you have to do with ACL is done on the ACL service.
You can start by creating it:

```php
// full configuration shown in the documentation
$acl = new ACL($entityManager);
```

You give permissions to a user by adding it a role:

```php
$acl->grant($user, new ArticleEditorRole($user, $article));
```

Roles are classes that you write and which define the permissions a user has on a resource.

You remove permissions to a user by removing the role:

```php
$acl->unGrant($user, $role);
```

Test permissions:

```php
$acl->isAllowed($user, Actions::EDIT, $article);
```

You can also filter your queries to get only the entities the user has access to:

```php
$qb = $entityManager->createQueryBuilder();
$qb->select('article')->from('Model\Article', 'article');

ACLQueryHelper::joinACL($qb, $user, Actions::EDIT);

// This query will return only the articles the user can edit
$articles = $qb->getQuery()->getResult();
```

### Features

- stored in database (you don't need to handle persistence yourself)
- extremely optimized:
  - filters queries at database level (you don't load entities the user can't access)
  - joins with only 1 extra table
- authorization cascading/inheritance
- authorizations are rebuildable: you can change what an "ArticleEditor" can do afterwards and just rebuild the ACL
- supports your custom actions on top of standard actions like "view", "edit", "delete", …

### Limitations

- because of Doctrine limitations you need to flush your resources before giving or testing authorizations
- backed up by the database: testing `isAllowed` means one call to the database
