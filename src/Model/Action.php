<?php

namespace MyCLabs\ACL\Model;

use InvalidArgumentException;
use MyCLabs\Enum\Enum;

/**
 * Actions that can be done on resources.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class Action extends Enum
{
    const VIEW = 'view';
    const CREATE = 'create';
    const EDIT = 'edit';
    const DELETE = 'delete';
    const UNDELETE = 'undelete';
    const ALLOW = 'allow';

    /**
     * @return Action
     */
    public static function VIEW()
    {
        return new static(self::VIEW);
    }

    /**
     * @return Action
     */
    public static function CREATE()
    {
        return new static(self::CREATE);
    }

    /**
     * @return Action
     */
    public static function EDIT()
    {
        return new static(self::EDIT);
    }

    /**
     * @return Action
     */
    public static function DELETE()
    {
        return new static(self::DELETE);
    }

    /**
     * @return Action
     */
    public static function UNDELETE()
    {
        return new static(self::UNDELETE);
    }

    /**
     * @return Action
     */
    public static function ALLOW()
    {
        return new static(self::ALLOW);
    }

    /**
     * @return string
     */
    public function exportToString()
    {
        return get_class($this) . '::' . $this->getValue();
    }

    /**
     * @param string $str
     * @throws InvalidArgumentException
     * @return Action
     */
    public static function importFromString($str)
    {
        if ($str === null) {
            throw new InvalidArgumentException("Unable to resolve ACL Action from null string");
        }

        $array = explode('::', $str, 2);
        if (count($array) != 2) {
            throw new InvalidArgumentException("Unable to resolve ACL Action: $str");
        }

        $class = $array[0];
        $enumValue = $array[1];

        return new $class($enumValue);
    }
}
