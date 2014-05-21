<?php
/**
 * Performance test for cascading authorizations when granting a role on a parent resource.
 */

require_once __DIR__ . '/setup.php';

// Cascading from Category to Article
foreach ($users as $user) {
    foreach ($categories as $i => $category) {
        $acl->grant($user, 'CategoryManager', $category);
        $acl->revoke($user, 'CategoryManager', $category);
    }
}

// Cascading from "All articles" to Article
foreach ($users as $user) {
    $acl->grant($user, 'AllArticlesEditor');
    $acl->revoke($user, 'AllArticlesEditor');
}
