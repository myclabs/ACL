<?php

namespace MyCLabs\ACL\Model;

/**
 * Class field resource.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
final class ClassFieldResource implements ResourceInterface
{
    /**
     * @var string
     */
    private $class;

    /**
     * @var string
     */
    private $field;

    /**
     * @param string $class Class name.
     * @param string $field Field name.
     */
    public function __construct($class, $field)
    {
        $this->class = $class;
        $this->field = $field;
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

    /**
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }
}
