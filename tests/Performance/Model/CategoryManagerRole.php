<?php

namespace Tests\MyCLabs\ACL\Performance\Model;

use Doctrine\ORM\Mapping as ORM;
use MyCLabs\ACL\ACL;
use MyCLabs\ACL\Model\Actions;
use MyCLabs\ACL\Model\Role;

/**
 * @ORM\Entity(readOnly=true)
 */
class CategoryManagerRole extends Role
{
    /**
     * @var Category
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="roles")
     */
    protected $category;

    public function __construct(User $identity, Category $category)
    {
        $this->category = $category;

        parent::__construct($identity);
    }

    public function createAuthorizations(ACL $acl)
    {
        $acl->allow($this, new Actions([Actions::VIEW]), $this->category);
    }
}
