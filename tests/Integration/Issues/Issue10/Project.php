<?php

namespace Tests\MyCLabs\ACL\Integration\Issues\Issue10;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use MyCLabs\ACL\Model\EntityResourceTrait;
use MyCLabs\ACL\Model\ResourceInterface;

/**
 * @ORM\Entity
 */
class Project implements ResourceInterface
{
    use EntityResourceTrait;

    /**
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var Account
     * @ORM\ManyToOne(targetEntity="Account", inversedBy="projects")
     **/
    private $account;

    /**
     * @var Item[]|Collection
     * @ORM\OneToMany(targetEntity="Item", mappedBy="project", cascade={"persist", "remove"})
     **/
    private $items;

    public function __construct(Account $account)
    {
        $this->items = new ArrayCollection();
        $this->account = $account;
    }

    public function getId()
    {
        return $this->id;
    }

    public function addItem(Item $item)
    {
        $this->items[] = $item;
    }

    /**
     * @return Account
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @return Item[]
     */
    public function getItems()
    {
        return $this->items->toArray();
    }
}
