<?php
/**
 * Performance test for cascading authorizations on newly created entities.
 */

use Tests\MyCLabs\ACL\Performance\Model\Article;
use Tests\MyCLabs\ACL\Performance\Model\Category;

require_once __DIR__ . '/setup.php';

// Create 10 new categories
/** @var Category[] $newCategories */
$newCategories = [];
for ($i = 0; $i < 10; $i++) {
    $category = new Category();
    $em->persist($category);
    $newCategories[$i] = $category;
}
$em->flush();

// Add roles on those categories
foreach ($users as $user) {
    foreach ($newCategories as $newCategory) {
        $acl->grant($user, 'CategoryManager', $newCategory);
    }
}

// Add new articles (authorizations will cascade from categories)
foreach ($newCategories as $newCategory) {
    for ($j = 0; $j < 100; $j++) {
        $newArticle = new Article($newCategory);
        $newCategory->addArticle($newArticle);
        $em->persist($newArticle);
    }
}
$em->flush();
