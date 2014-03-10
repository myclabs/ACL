<?php

namespace MyCLabs\ACL\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;

/**
 * Role.
 *
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
abstract class Role
{
    /**
     * @var int
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var SecurityIdentityInterface
     * @ORM\ManyToOne(targetEntity="SecurityIdentityInterface", inversedBy="roles")
     */
    protected $securityIdentity;

    /**
     * @var Authorization[]|Collection
     * @ORM\OneToMany(targetEntity="Authorization", mappedBy="role")
     */
    protected $authorizations;

    public function __construct(SecurityIdentityInterface $identity)
    {
        $this->authorizations = new ArrayCollection();
        $this->securityIdentity = $identity;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * @return Authorization[]
     */
    public function getRootAuthorizations()
    {
        $criteria = new Criteria();
        $criteria->where($criteria->expr()->isNull('parentAuthorization'));

        return $this->authorizations->matching($criteria);
    }

    /**
     * @return SecurityIdentityInterface
     */
    public function getSecurityIdentity()
    {
        return $this->securityIdentity;
    }

    public function addAuthorization(Authorization $authorization)
    {
        $this->authorizations[] = $authorization;
    }
}
