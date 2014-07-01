<?php

namespace MyCLabs\ACL\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Tools\ResolveTargetEntityListener;
use MyCLabs\ACL\Model\Identity;

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
    private $identityClass;

    public function __construct()
    {
        $this->metadataLoader = new ACLMetadataLoader();
    }

    public function setUpEntityManager(EntityManager $entityManager, callable $aclLocator)
    {
        if ($this->identityClass === null) {
            throw new \RuntimeException(
                'The identity class must be configured: call $aclSetup->setIdentityClass("...")'
            );
        }

        $evm = $entityManager->getEventManager();

        // Configure which entity implements the Identity interface
        $rtel = new ResolveTargetEntityListener();
        $rtel->addResolveTargetEntity(Identity::class, $this->identityClass, []);
        $evm->addEventListener(Events::loadClassMetadata, $rtel);

        // Register the metadata loader
        $evm->addEventListener(Events::loadClassMetadata, $this->metadataLoader);

        // Register the listener that looks for new resources
        $evm->addEventSubscriber(new EntityResourcesListener($aclLocator));
    }

    /**
     * Register which class is the identity. Must be called exactly once.
     *
     * @param string $class
     *
     * @throws \InvalidArgumentException The given class doesn't implement the Identity interface
     */
    public function setIdentityClass($class)
    {
        if (! is_subclass_of($class, Identity::class)) {
            throw new \InvalidArgumentException("The given class doesn't implement the Identity interface");
        }

        $this->identityClass = $class;
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
