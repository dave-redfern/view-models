<?php

namespace AppBundle\Entities\ValueObjects;

/**
 * Class Aggregate
 *
 * @package    AppBundle\Entities\ValueObjects
 * @subpackage AppBundle\Entities\ValueObjects\Aggregate
 */
class Aggregate extends AbstractValueObject
{

    /**
     * @var string
     */
    private $class;

    /**
     * @var string
     */
    private $id;

    /**
     * Constructor.
     *
     * @param string $class
     * @param string $id
     */
    public function __construct(string $class, $id)
    {
        $this->class = $class;
        $this->id    = $id;
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return sprintf('%s:%s', $this->class, $this->id);
    }

    /**
     * @return string
     */
    public function class(): string
    {
        return $this->class;
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return (string)$this->id;
    }
}
