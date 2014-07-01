<?php

namespace MyCLabs\ACL\Doctrine;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use MyCLabs\ACL\Model\Actions;
use MyCLabs\ACL\Model\Authorization;

/**
 * Loads metadata relative to ACL in Doctrine.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class ACLMetadataLoader
{
    /**
     * @var string
     */
    private $actionsClass;

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
        if (! is_subclass_of($class, Actions::class)) {
            throw new \InvalidArgumentException("The given class doesn't extend " . Actions::class);
        }

        $this->actionsClass = $class;
    }

    /**
     * Overrides the discriminator maps for class table inheritance for roles and authorizations.
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        /** @var ClassMetadata $metadata */
        $metadata = $eventArgs->getClassMetadata();

        if (($this->actionsClass !== null) && ($metadata->getName() === Authorization::class)) {
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
