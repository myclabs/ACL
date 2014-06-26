<?php

namespace Tests\MyCLabs\ACL\Integration\Issues\Issue10;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use MyCLabs\ACL\Model\CascadingResource;
use MyCLabs\ACL\Model\EntityResource;

/**
 * @ORM\Entity
 */
class Account implements EntityResource, CascadingResource
{
    /**
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var Project[]
     * @ORM\OneToMany(targetEntity="Project", mappedBy="account", cascade={"persist", "remove"})
     **/
    private $projects;

    public function __construct()
    {
        $this->projects = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function addProject(Project $project)
    {
        $this->projects[] = $project;
    }

    public function getParentResources(EntityManager $entityManager)
    {
        return [];
    }

    public function getSubResources(EntityManager $entityManager)
    {
        return $this->projects->toArray();
    }
}
