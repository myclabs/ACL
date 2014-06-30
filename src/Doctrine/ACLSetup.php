<?php

namespace MyCLabs\ACL\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Tools\ResolveTargetEntityListener;
use MyCLabs\ACL\ACL;
use MyCLabs\ACL\Model\SecurityIdentityInterface;

/**
 * Configures the entity manager.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class ACLSetup
{
    /**
     * @var ACLMetadataLoader
     */
    private $metadataLoader;

    /**
     * @var string
     */
    private $securityIdentityClass;

    public function __construct()
    {
        $this->metadataLoader = new ACLMetadataLoader();
    }

    public function setUpEntityManager(EntityManager $entityManager, callable $aclLocator)
    {
        if ($this->securityIdentityClass === null) {
            throw new \RuntimeException(
                'The security identity class must be configured: call ->setSecurityIdentityClass("...")'
            );
        }

        $evm = $entityManager->getEventManager();

        // Configure which entity implements the SecurityIdentityInterface
        $rtel = new ResolveTargetEntityListener();
        $rtel->addResolveTargetEntity(SecurityIdentityInterface::class, $this->securityIdentityClass, []);
        $evm->addEventListener(Events::loadClassMetadata, $rtel);

        // Register the metadata loader
        $evm->addEventListener(Events::loadClassMetadata, $this->metadataLoader);

        // Register the listener that looks for new resources
        $evm->addEventSubscriber(new EntityResourcesListener($aclLocator));
    }

    /**
     * Register which class is the security identity. Must be called exactly once.
     *
     * @param string $class
     *
     * @throws \InvalidArgumentException The given class doesn't implement the SecurityIdentityInterface interface
     */
    public function setSecurityIdentityClass($class)
    {
        if (! is_subclass_of($class, SecurityIdentityInterface::class)) {
            throw new \InvalidArgumentException("The given class doesn't implement SecurityIdentityInterface");
        }

        $this->securityIdentityClass = $class;
    }

    /**
     * Registers an alternative "Actions" class to use in the authorization entity.
     *
     * This allows to write your own actions.
     *
     * @param string $class
     *
     * @throws \InvalidArgumentException The given class doesn't extend MyCLabs\ACL\Model\Actions
     */
    public function setActionsClass($class)
    {
        $this->metadataLoader->setActionsClass($class);
    }

    /**
     * @todo Move to ACL
     * @param array $roles
     * @param ACL   $acl
     */
    public function registerRoles(array $roles, ACL $acl)
    {
        // send to metadataloader
        /** @var ACL $acl */
        //$acl = $aclLocator();
        $acl->setRoles($roles);
    }
}
