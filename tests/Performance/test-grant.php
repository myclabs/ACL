<?php
/**
 * Performance test for "grant" and "revoke".
 */

require_once __DIR__ . '/setup.php';

foreach ($users as $user) {
    foreach ($articles as $article) {
        $acl->grant($user, 'ArticleEditor', $article);
        $acl->revoke($user, 'ArticleEditor', $article);
    }
}
