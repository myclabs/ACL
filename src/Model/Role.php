<?php

namespace MyCLabs\ACL\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

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
     * @OneToMany(targetEntity="Authorization", mappedBy="product")
     */
    protected $authorizations;

    public function __construct(SecurityIdentityInterface $identity)
    {
        $this->authorizations = new ArrayCollection();
        $this->securityIdentity = $identity;
    }

    /**
     * Creates the role's authorizations.
     *
     * @return Authorization[]
     */
    abstract public function createAuthorizations();

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return SecurityIdentityInterface
     */
    public function getSecurityIdentity()
    {
        return $this->securityIdentity;
    }
}
