<?php

namespace AppBundle\Support;

/**
 * Class EntityAccessor
 *
 * @package    AppBundle\Support
 * @subpackage AppBundle\Support\EntityAccessor
 */
class EntityAccessor
{

    /**
     * Helper to allow accessing protected properties without an accessor
     *
     * @param object $object
     * @param string $property
     *
     * @return mixed
     */
    public static function get($object, $property)
    {
        $refObject = new \ReflectionObject($object);
        $refProp   = $refObject->getProperty($property);
        $refProp->setAccessible(true);

        return $refProp->getValue($object);
    }
}
