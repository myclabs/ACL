<?php

namespace MyCLabs\ACL;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Tools\ResolveTargetEntityListener;
use MyCLabs\ACL\Model\Actions;
use MyCLabs\ACL\Model\Role;
use MyCLabs\ACL\Model\SecurityIdentityInterface;

/**
 * Configures the entity manager.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class EntityManagerSetup
{
    /**
     * @var MetadataLoader
     */
    private $metadataLoader;

    /**
     * @var string
     */
    private $securityIdentityClass;

    public function __construct()
    {
        $this->metadataLoader = new MetadataLoader();
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
        $evm->addEventSubscriber(new EntityManagerListener($aclManagerLocator));
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
        if (! $class instanceof SecurityIdentityInterface) {
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
        if (! $class instanceof Role) {
            throw new \InvalidArgumentException('The given class doesn\'t extend MyCLabs\ACL\Model\Role');
        }

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
        if (! $class instanceof Actions) {
            throw new \InvalidArgumentException('The given class doesn\'t extend MyCLabs\ACL\Model\Actions');
        }

        $this->metadataLoader->setActionsClass($class);
    }
}
