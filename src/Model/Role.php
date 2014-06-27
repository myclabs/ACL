<?php

namespace MyCLabs\ACL\Model;

use Doctrine\Common\Util\ClassUtils;
use InvalidArgumentException;
use MyCLabs\ACL\ACL;

/**
 * Represents a role.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class Role
{
    /**
     * Name of the role.
     *
     * @var string
     */
    private $name;

    /**
     * List of actions to give when the role is granted.
     *
     * If null, will use $authorizationsBuilder.
     *
     * @var Actions|null
     */
    private $actions;

    /**
     * This is a closure that will build the authorizations for the role entry.
     *
     * Must take 3 parameters: ACL, RoleEntry, ResourceInterface
     *
     * If null, will use $actions.
     *
     * @var \Closure|null
     */
    private $authorizationsBuilder;

    /**
     * Defined if the whole role applies to a single resource (usually a ClassResource).
     *
     * @var ResourceInterface|null
     */
    private $resource;

    /**
     * Used to limit the type of a resource (usually an EntityResource).
     *
     * @var string|null
     */
    private $resourceType;

    /**
     * Build a role from an array.
     *
     * @param string $name
     * @param array  $configuration
     *
     * @throws InvalidArgumentException
     * @return Role
     */
    public static function fromArray($name, array $configuration)
    {
        $role = new self($name);

        if (isset($configuration['actions'])) {
            if (is_array($configuration['actions'])) {
                $role->actions = new Actions($configuration['actions']);
            } else {
                $role->actions = $configuration['actions'];
            }
        }

        if (isset($configuration['authorizations']) && $configuration['authorizations'] instanceof \Closure) {
            $role->authorizationsBuilder = $configuration['authorizations'];
        }

        if (isset($configuration['resource'])) {
            if (! $configuration['resource'] instanceof ResourceInterface) {
                throw new InvalidArgumentException(sprintf(
                    "The resource configured for role %s doesn't implement ResourceInterface",
                    $name
                ));
            }

            $role->resource = $configuration['resource'];
        }

        if (isset($configuration['resourceType'])) {
            if (isset($configuration['resource'])) {
                throw new InvalidArgumentException(sprintf(
                    "It isn't possible to configure the role %s both with 'resource' and 'resourceType'",
                    $name
                ));
            }

            $role->resourceType = $configuration['resourceType'];
        }

        return $role;
    }

    /**
     * Create the authorizations for the given role entry and resource.
     *
     * @param ACL               $acl
     * @param RoleEntry         $roleEntry
     * @param ResourceInterface $resource
     */
    public function createAuthorizations(ACL $acl, RoleEntry $roleEntry, ResourceInterface $resource)
    {
        if ($this->actions) {
            $acl->allow($roleEntry, $this->actions, $resource);
        }

        if (isset($this->authorizationsBuilder)) {
            $builder = $this->authorizationsBuilder;
            $builder($acl, $roleEntry, $resource);
        }
    }

    /**
     * This will validate that the role is granted on a correct resource.
     * It will also return the resource the role should be granted upon in case the resource is null.
     *
     * @param ResourceInterface|null $resource
     *
     * @throws InvalidArgumentException
     * @return ResourceInterface
     */
    public function validateAndReturnResourceForGrant(ResourceInterface $resource = null)
    {
        if (isset($this->resource)) {
            if ($resource) {
                throw new InvalidArgumentException(sprintf(
                    'Cannot grant role %s to a resource of type %s. The resource %s should be'
                    . ' granted upon is set in the configuration and cannot be overridden',
                    $this->name,
                    ClassUtils::getClass($resource)
                ));
            }

            return $this->resource;
        }

        if ($resource === null) {
            throw new InvalidArgumentException(sprintf(
                'The role %s must be granted to a resource, no resource was given',
                $this->name
            ));
        }

        if (isset($this->resourceType) && (! $resource instanceof $this->resourceType)) {
            throw new InvalidArgumentException(sprintf(
                'Cannot grant role %s to a resource of type %s. Per the configuration, %s can only be granted'
                . ' on resources of type %s',
                $this->name,
                ClassUtils::getClass($resource),
                $this->resourceType
            ));
        }

        return $resource;
    }

    private function __construct($name)
    {
        $this->name = (string) $name;
    }
}
