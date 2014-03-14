<?php

namespace MyCLabs\ACL\Model;

/**
 * Class resource.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
final class ClassResource implements ResourceInterface
{
    /**
     * @var string
     */
    private $class;

    /**
     * @param string $class Class name.
     */
    public function __construct($class)
    {
        $this->class = $class;
    }

    /**
     * Returns the name of the class.
     *
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }
}
