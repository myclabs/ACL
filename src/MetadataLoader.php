<?php

namespace MyCLabs\ACL;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;

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
     * @var string
     */
    private $actionsClass;

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
     * @param string $class
     */
    public function setActionsClass($class)
    {
        $this->actionsClass = $class;
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

        if (($this->actionsClass !== null) && ($metadata->getName() === 'MyCLabs\ACL\Model\Authorization')) {
            $this->remapActions($metadata, $eventArgs->getEntityManager()->getMetadataFactory());
        }
    }

    private function remapActions(ClassMetadata $metadata, ClassMetadataFactory $metadataFactory)
    {
        $fieldName = 'actions';

        unset($metadata->fieldMappings[$fieldName]);
        unset($metadata->embeddedClasses[$fieldName]);

        // Re-map the embeddable
        $mapping = [
            'fieldName'    => $fieldName,
            'class'        => $this->actionsClass,
            'columnPrefix' => null,
        ];
        $metadata->mapEmbedded($mapping);

        // Remove the existing inlined fields
        foreach ($metadata->fieldMappings as $name => $fieldMapping) {
            if (isset($fieldMapping['declaredField']) && $fieldMapping['declaredField'] === $fieldName) {
                unset($metadata->fieldMappings[$name]);
                unset($metadata->fieldNames[$fieldMapping['columnName']]);
            }
        }

        // Re-inline the embeddable
        $embeddableMetadata = $metadataFactory->getMetadataFor($this->actionsClass);
        $metadata->inlineEmbeddable($fieldName, $embeddableMetadata);
    }
}
