<?php

namespace MyCLabs\ACL\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Tools\ResolveTargetEntityListener;
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

    public function setUpEntityManager(EntityManager $entityManager, callable $aclManagerLocator)
    {
        if ($this->securityIdentityClass === null) {
            throw new \RuntimeException(
                'The security identity class must be configured: call ->setSecurityIdentityClass("...")'
            );
        }

        $evm = $entityManager->getEventManager();

        // Configure which entity implements the SecurityIdentityInterface
        $rtel = new ResolveTargetEntityListener();
        $rtel->addResolveTargetEntity('MyCLabs\ACL\Model\SecurityIdentityInterface', $this->securityIdentityClass, []);
        $evm->addEventListener(Events::loadClassMetadata, $rtel);

        // Register the metadata loader
        $evm->addEventListener(Events::loadClassMetadata, $this->metadataLoader);

        // Register the listener that looks for new resources
        $evm->addEventSubscriber(new EntityResourcesListener($aclManagerLocator));
    }

    /**
     * Register which class is the security identity. Must be called exactly once.
     *
     * @param string $class
     *
     * @throws \InvalidArgumentException The given class doesn't implement SecurityIdentityInterface
     */
    public function setSecurityIdentityClass($class)
    {
        if (! is_subclass_of($class, 'MyCLabs\ACL\Model\SecurityIdentityInterface')) {
            throw new \InvalidArgumentException('The given class doesn\'t implement SecurityIdentityInterface');
        }

        $this->securityIdentityClass = $class;
    }

    /**
     * Dynamically register a role subclass in the discriminator map for the Doctrine mapping.
     *
     * @param string $class
     * @param string $shortName
     *
     * @throws \InvalidArgumentException The given class doesn't extend MyCLabs\ACL\Model\Role
     */
    public function registerRoleClass($class, $shortName)
    {
        $this->metadataLoader->registerRoleClass($class, $shortName);
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
}
