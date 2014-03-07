<?php

namespace MyCLabs\ACL\Model;

/**
 * Actions that can be done on a resource.
 *
 * @Embeddable
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
     * @Column(type = "boolean")
     */
    public $view = false;

    /**
     * @Column(type = "boolean")
     */
    public $create = false;

    /**
     * @Column(type = "boolean")
     */
    public $edit = false;

    /**
     * @Column(type = "boolean")
     */
    public $delete = false;

    /**
     * @Column(type = "boolean")
     */
    public $undelete = false;

    /**
     * @Column(type = "boolean")
     */
    public $allow = false;

    public function __construct(array $actions = [])
    {
        foreach ($actions as $action) {
            if (property_exists($this, $action)) {
                $this->$action = true;
            }
        }
    }
}
