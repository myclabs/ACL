<?php

namespace Tests\MyCLabs\ACL\Integration\Issues;

use MyCLabs\ACL\ACL;
use MyCLabs\ACL\CascadeStrategy\SimpleCascadeStrategy;
use MyCLabs\ACL\Model\Actions;
use Tests\MyCLabs\ACL\Integration\AbstractIntegrationTest;
use Tests\MyCLabs\ACL\Integration\Issues\Issue10\Account;
use Tests\MyCLabs\ACL\Integration\Issues\Issue10\AccountAdminRole;
use Tests\MyCLabs\ACL\Integration\Issues\Issue10\Item;
use Tests\MyCLabs\ACL\Integration\Issues\Issue10\Project;
use Tests\MyCLabs\ACL\Integration\Issues\Issue10\ProjectGraphTraverser;
use Tests\MyCLabs\ACL\Integration\Model\User;

/**
 * Tests authorizations are cascading between parent and sub-resources.
 *
 * @coversNothing
 */
class Issue10Test extends AbstractIntegrationTest
{
    protected function createACL()
    {
        $cascadeStrategy = new SimpleCascadeStrategy($this->em);
        $cascadeStrategy->setResourceGraphTraverser(
            'Tests\MyCLabs\ACL\Integration\Issues\Issue10\Project',
            new ProjectGraphTraverser()
        );

        return new ACL($this->em, $cascadeStrategy);
    }

    /**
     * Authorizations created by a role should cascade.
     */
    public function testRoleAuthorizationShouldCascade()
    {
        $account = new Account();
        $project = new Project($account);
        $account->addProject($project);
        $item = new Item($project);
        $project->addItem($item);

        $user = new User();

        $this->em->persist($account);
        $this->em->persist($user);
        $this->em->flush();

        $this->acl->grant($user, 'accountAdmin', $account);

        $this->assertTrue($this->acl->isAllowed($user, Actions::VIEW, $account));
        $this->assertTrue($this->acl->isAllowed($user, Actions::VIEW, $project));

        // Check that the authorization has cascaded to the item
        $this->assertTrue($this->acl->isAllowed($user, Actions::VIEW, $item));
    }

    /**
     * A new resource should inherit the authorizations that exist on parent resources.
     */
    public function testNewResourceShouldInherit()
    {
        $account = new Account();

        $user = new User();

        $this->em->persist($account);
        $this->em->persist($user);
        $this->em->flush();

        $this->acl->grant($user, 'accountAdmin', $account);

        $this->assertTrue($this->acl->isAllowed($user, Actions::VIEW, $account));

        $project = new Project($account);
        $account->addProject($project);
        $this->em->flush();

        $this->assertTrue($this->acl->isAllowed($user, Actions::VIEW, $project));

        $item = new Item($project);
        $project->addItem($item);
        $this->em->flush();

        // Check that the authorization has cascaded to the item
        $this->assertTrue($this->acl->isAllowed($user, Actions::VIEW, $item));
    }
}
