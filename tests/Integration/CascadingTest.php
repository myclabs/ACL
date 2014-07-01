<?php

namespace Tests\MyCLabs\ACL\Integration;

use MyCLabs\ACL\Model\Actions;
use Tests\MyCLabs\ACL\Integration\Model\Category;
use Tests\MyCLabs\ACL\Integration\Model\User;

/**
 * Tests authorizations are cascading between parent and sub-resources.
 *
 * @coversNothing
 */
class CascadingTest extends AbstractIntegrationTest
{
    /**
     * Authorizations created by a role should cascade.
     */
    public function testRoleAuthorizationShouldCascade()
    {
        $category1 = new Category();
        $this->em->persist($category1);
        $category2 = new Category($category1);
        $this->em->persist($category2);
        $category3 = new Category($category2);
        $this->em->persist($category3);

        $user = new User();
        $this->em->persist($user);

        $this->em->flush();

        $this->acl->grant($user, 'CategoryManager', $category1);

        // Direct authorization
        $this->assertTrue($this->acl->isAllowed($user, Actions::VIEW, $category1));
        $this->assertFalse($this->acl->isAllowed($user, Actions::EDIT, $category1));

        // Cascaded authorization
        $this->assertTrue($this->acl->isAllowed($user, Actions::VIEW, $category2));
        $this->assertFalse($this->acl->isAllowed($user, Actions::EDIT, $category2));

        // Should also cascade recursively, on sub-resources of sub-resources
        $this->assertTrue($this->acl->isAllowed($user, Actions::VIEW, $category3));
        $this->assertFalse($this->acl->isAllowed($user, Actions::EDIT, $category3));
    }

    /**
     * A new resource should inherit the authorizations that exist on parent resources.
     */
    public function testNewResourceShouldInherit()
    {
        $category1 = new Category();
        $this->em->persist($category1);

        $user = new User();
        $this->em->persist($user);

        $this->em->flush();

        $this->acl->grant($user, 'CategoryManager', $category1);

        // Direct authorization
        $this->assertTrue($this->acl->isAllowed($user, Actions::VIEW, $category1));
        $this->assertFalse($this->acl->isAllowed($user, Actions::EDIT, $category1));

        // Cascaded authorization
        $category2 = new Category($category1);
        $this->em->persist($category2);
        $this->em->flush();
        $this->assertTrue($this->acl->isAllowed($user, Actions::VIEW, $category2));
        $this->assertFalse($this->acl->isAllowed($user, Actions::EDIT, $category2));

        // Should also cascade recursively, on sub-resources of sub-resources
        $category3 = new Category($category2);
        $this->em->persist($category3);
        $this->em->flush();
        $this->assertTrue($this->acl->isAllowed($user, Actions::VIEW, $category3));
        $this->assertFalse($this->acl->isAllowed($user, Actions::EDIT, $category3));
    }
}
