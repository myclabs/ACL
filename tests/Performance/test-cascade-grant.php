<?php
/**
 * Performance test for cascading authorizations when granting a role on a parent resource.
 */

use Tests\MyCLabs\ACL\Performance\Model\AllArticlesEditorRole;
use Tests\MyCLabs\ACL\Performance\Model\CategoryManagerRole;

require_once __DIR__ . '/setup.php';

// Cascading from Category to Article
foreach ($users as $user) {
    foreach ($categories as $i => $category) {
        $role = new CategoryManagerRole($user, $category);
        $acl->grant($user, $role);
        $acl->revoke($user, $role);
    }
}

// Cascading from "All articles" to Article
foreach ($users as $user) {
    $role = new AllArticlesEditorRole($user);
    $acl->grant($user, $role);
    $acl->revoke($user, $role);
}
