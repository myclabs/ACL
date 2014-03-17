<?php

namespace MyCLabs\ACL;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Loads metadata relative to ACL in Doctrine.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class MetadataLoader
{
    /**
     * Discriminator map for roles.
     * @var string[]
     */
    private $roles = [];

    /**
     * Dynamically register a role subclass in the discriminator map for the Doctrine mapping.
     *
     * @param string $class
     * @param string $shortName
     */
    public function registerRoleClass($class, $shortName)
    {
        $this->roles[$shortName] = $class;
    }

    /**
     * Overrides the discriminator maps for class table inheritance for roles and authorizations.
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        /** @var ClassMetadata $metadata */
        $metadata = $eventArgs->getClassMetadata();

        if ($metadata->getName() === 'MyCLabs\ACL\Model\Role') {
            $metadata->setDiscriminatorMap($this->roles);
        }
    }
}
