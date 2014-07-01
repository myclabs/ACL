<?php

namespace Tests\MyCLabs\ACL\Integration;

use MyCLabs\ACL\Model\Actions;
use Tests\MyCLabs\ACL\Integration\Model\Article;
use Tests\MyCLabs\ACL\Integration\Model\Category;
use Tests\MyCLabs\ACL\Integration\Model\User;

/**
 * Tests that authorizations are deleted when a resource is deleted.
 *
 * @coversNothing
 */
class ResourceDeletionTest extends AbstractIntegrationTest
{
    public function testSimple()
    {
        $resource = new Article();
        $this->em->persist($resource);
        $user = new User();
        $this->em->persist($user);
        $this->em->flush();

        // The role will create 1 authorization
        $this->acl->grant($user, 'ArticleEditor', $resource);
        $this->assertTrue($this->acl->isAllowed($user, Actions::VIEW, $resource));

        // We need to reload the resource because the role hasn't been added automatically to
        // the role collection in Article
        $this->em->refresh($resource);

        // Now we delete the resource
        $this->em->remove($resource);
        $this->em->flush();

        // We check that the authorization is deleted
        $query = $this->em->createQuery('SELECT COUNT(a.id) FROM MyCLabs\ACL\Model\Authorization a');
        $this->assertEquals(0, $query->getSingleScalarResult(), "The authorization wasn't deleted");

        // We check that the role is deleted too
        $query = $this->em->createQuery('SELECT COUNT(r.id) FROM MyCLabs\ACL\Model\RoleEntry r');
        $this->assertEquals(0, $query->getSingleScalarResult(), "The role wasn't deleted");
    }

    /**
     * Here we delete a resource which had no direct roles associated. However it had authorizations
     * because of a parent resource.
     *
     * @link https://github.com/myclabs/ACL/issues/12
     */
    public function testDeletionWithCascade()
    {
        $category = new Category();
        $this->em->persist($category);
        $subCategory = new Category($category);
        $this->em->persist($subCategory);
        $user = new User();
        $this->em->persist($user);
        $this->em->flush();

        // We apply a role on the parent resource, authorizations will cascade to the sub-resource
        $this->acl->grant($user, 'CategoryManager', $category);
        $this->assertTrue($this->acl->isAllowed($user, Actions::VIEW, $category));
        $this->assertTrue($this->acl->isAllowed($user, Actions::VIEW, $subCategory));

        // We need to reload the resource because the role hasn't been added automatically to
        // the role collection in Category
        $this->em->refresh($category);

        // Now we delete the sub-resource
        $this->em->remove($subCategory);
        $this->em->flush();

        // We check that the authorization is deleted (there should be 1 left: the one for the parent category)
        $query = $this->em->createQuery('SELECT COUNT(a.id) FROM MyCLabs\ACL\Model\Authorization a');
        $this->assertEquals(1, $query->getSingleScalarResult(), "The child authorization wasn't deleted");

        // We check that the role is not deleted
        $query = $this->em->createQuery('SELECT COUNT(r.id) FROM MyCLabs\ACL\Model\RoleEntry r');
        $this->assertEquals(1, $query->getSingleScalarResult());

        // We check that isAllowed still works with the parent resource (which wasn't deleted)
        $this->assertTrue($this->acl->isAllowed($user, Actions::VIEW, $category));
    }
}
