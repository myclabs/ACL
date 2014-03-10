<?php

namespace MyCLabs\ACL\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;

/**
 * Role.
 *
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="type", type="string")
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
abstract class Role
{
    /**
     * @var int
     * @Id @GeneratedValue
     * @Column(type="integer")
     */
    protected $id;

    /**
     * @var SecurityIdentityInterface
     * @ManyToOne(targetEntity="SecurityIdentityInterface", inversedBy="roles")
     */
    protected $securityIdentity;

    /**
     * @var Authorization[]|Collection
     * @OneToMany(targetEntity="Authorization", mappedBy="role")
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
