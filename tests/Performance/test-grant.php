<?php
/**
 * Performance test for "grant" and "revoke".
 */

use Tests\MyCLabs\ACL\Performance\Model\ArticleEditorRole;

require_once __DIR__ . '/setup.php';

foreach ($users as $user) {
    foreach ($articles as $article) {
        $role = new ArticleEditorRole($user, $article);
        $acl->grant($user, $role);
        $acl->revoke($user, $role);
    }
}
