<?php

namespace Tests\MyCLabs\ACL\Integration;

use MyCLabs\ACL\Model\Actions;
use MyCLabs\ACL\Model\VirtualResource;
use Tests\MyCLabs\ACL\Integration\Model\User;

/**
 * Tests authorizations applied on virtual resources.
 *
 * @coversNothing
 */
class VirtualResourceTest extends AbstractIntegrationTest
{
    /**
     * Check when adding the role and all entities are already created and flushed
     */
    public function testIsAllowed()
    {
        $user1 = new User();
        $this->em->persist($user1);
        $user2 = new User();
        $this->em->persist($user2);

        $this->em->flush();

        $resource = new VirtualResource('backend');

        $this->assertFalse($this->acl->isAllowed($user1, Actions::VIEW, $resource));
        $this->assertFalse($this->acl->isAllowed($user1, Actions::EDIT, $resource));
        $this->assertFalse($this->acl->isAllowed($user2, Actions::VIEW, $resource));

        $this->acl->grant($user1, 'BackendAdmin');

        $this->assertTrue($this->acl->isAllowed($user1, Actions::VIEW, $resource));
        $this->assertFalse($this->acl->isAllowed($user1, Actions::EDIT, $resource));
        $this->assertFalse($this->acl->isAllowed($user2, Actions::VIEW, $resource));

        $this->acl->revoke($user1, 'BackendAdmin');

        $this->assertFalse($this->acl->isAllowed($user1, Actions::VIEW, $resource));
        $this->assertFalse($this->acl->isAllowed($user1, Actions::EDIT, $resource));
        $this->assertFalse($this->acl->isAllowed($user2, Actions::VIEW, $resource));
    }
}
