<?php
/**
 * Performance test for "isAllowed".
 */

use MyCLabs\ACL\Model\Actions;

require_once __DIR__ . '/setup.php';

foreach ($users as $user) {
    foreach ($articles as $article) {
        $acl->isAllowed($user, Actions::VIEW, $article);
    }
}
