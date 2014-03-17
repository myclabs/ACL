<?php

namespace MyCLabs\ACL\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Actions that can be done on a resource.
 *
 * @ORM\Embeddable
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class Actions
{
    const VIEW = 'view';
    const CREATE = 'create';
    const EDIT = 'edit';
    const DELETE = 'delete';
    const UNDELETE = 'undelete';
    const ALLOW = 'allow';

    /**
     * @ORM\Column(type = "boolean")
     */
    public $view = false;

    /**
     * @ORM\Column(type = "boolean")
     */
    public $create = false;

    /**
     * @ORM\Column(type = "boolean")
     */
    public $edit = false;

    /**
     * @ORM\Column(type = "boolean")
     */
    public $delete = false;

    /**
     * @ORM\Column(type = "boolean")
     */
    public $undelete = false;

    /**
     * @ORM\Column(type = "boolean")
     */
    public $allow = false;

    public function __construct(array $actions = [])
    {
        foreach ($actions as $action) {
            if (property_exists($this, $action)) {
                $this->$action = true;
            } else {
                throw new \InvalidArgumentException('Unknown ACL action ' . $action);
            }
        }
    }

    /**
     * Returns all actions as an array of values (boolean) indexed by the action name.
     *
     * @return array
     */
    public function toArray()
    {
        return get_object_vars($this);
    }

    /**
     * Return an object representing all actions.
     *
     * @return Actions
     */
    public static function all()
    {
        return new static([
            static::VIEW,
            static::CREATE,
            static::EDIT,
            static::DELETE,
            static::UNDELETE,
            static::ALLOW,
        ]);
    }
}
