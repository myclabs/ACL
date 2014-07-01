<?php
/**
 * Performance test for "rebuildAuthorizations"
 */

require_once __DIR__ . '/setup.php';

foreach ($users as $user) {
    foreach ($categories as $category) {
        $acl->grant($user, 'CategoryManager', $category);
    }
}

$acl->rebuildAuthorizations();
