<?php

namespace Tests\MyCLabs\ACL\Integration\Issues\Issue10;

use Doctrine\ORM\Mapping as ORM;
use MyCLabs\ACL\ACL;
use MyCLabs\ACL\Model\Role;
use Tests\MyCLabs\ACL\Integration\Model\Actions;
use Tests\MyCLabs\ACL\Integration\Model\User;

/**
 * @ORM\Entity(readOnly=true)
 */
class AccountAdminRole extends Role
{
    /**
     * @var Account
     * @ORM\ManyToOne(targetEntity="Account", inversedBy="roles")
     */
    protected $account;

    public function __construct(User $identity, Account $account)
    {
        $this->account = $account;

        parent::__construct($identity);
    }

    public function createAuthorizations(ACL $acl)
    {
        $acl->allow($this, Actions::all(), $this->account);
    }
}
