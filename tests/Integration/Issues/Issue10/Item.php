<?php

namespace Tests\MyCLabs\ACL\Integration\Issues\Issue10;

use Doctrine\ORM\Mapping as ORM;
use MyCLabs\ACL\Model\EntityResource;

/**
 * @ORM\Entity
 */
class Item implements EntityResource
{
    /**
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var Project
     * @ORM\ManyToOne(targetEntity="Project", inversedBy="items")
     **/
    private $project;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function getId()
    {
        return $this->id;
    }
}
