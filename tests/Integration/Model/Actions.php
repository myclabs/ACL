<?php

namespace Tests\MyCLabs\ACL\Integration\Model;

use Doctrine\ORM\Mapping as ORM;
use MyCLabs\ACL\Model\Actions as BaseActions;

/**
 * Custom actions.
 *
 * @ORM\Embeddable
 */
class Actions extends BaseActions
{
    const PUBLISH = 'publish';

    /**
     * @ORM\Column(type = "boolean")
     */
    public $publish = false;

    /**
     * {@inheritdoc}
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
            static::PUBLISH,
        ]);
    }
}
