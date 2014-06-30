<?php

namespace Tests\MyCLabs\ACL\Integration\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use MyCLabs\ACL\Model\CascadingResource;
use MyCLabs\ACL\Model\ClassResource;
use MyCLabs\ACL\Model\EntityResourceTrait;
use MyCLabs\ACL\Model\ResourceInterface;

/**
 * @ORM\Entity
 */
class Article implements ResourceInterface, CascadingResource
{
    use EntityResourceTrait;

    /**
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    public function getId()
    {
        return $this->id;
    }

    public function getParentResources(EntityManager $entityManager)
    {
        return [
            new ClassResource(get_class()),
        ];
    }

    public function getSubResources(EntityManager $entityManager)
    {
        return [];
    }
}
