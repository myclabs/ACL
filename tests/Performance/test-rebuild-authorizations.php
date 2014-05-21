<?php
/**
 * Performance test for "rebuildAuthorizations"
 */

use Tests\MyCLabs\ACL\Performance\Model\CategoryManagerRole;

require_once __DIR__ . '/setup.php';

foreach ($users as $user) {
    foreach ($categories as $category) {
        $acl->grant($user, new CategoryManagerRole($user, $category));
    }
}

$acl->rebuildAuthorizations();
