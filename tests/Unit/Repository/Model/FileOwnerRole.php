<?php

namespace Tests\MyCLabs\ACL\Unit\Repository\Model;

use Doctrine\ORM\Mapping as ORM;
use MyCLabs\ACL\ACL;
use MyCLabs\ACL\Model\Actions;
use MyCLabs\ACL\Model\Role;

/**
 * @ORM\Entity(readOnly=true)
 */
class FileOwnerRole extends Role
{
    /**
     * @var File
     * @ORM\ManyToOne(targetEntity="File", inversedBy="roles")
     */
    protected $file;

    public function __construct(User $identity, File $file)
    {
        $this->file = $file;

        parent::__construct($identity);
    }

    public function createAuthorizations(ACL $acl)
    {
        $acl->allow($this, new Actions([Actions::VIEW, Actions::EDIT]), $this->file);
    }
}
