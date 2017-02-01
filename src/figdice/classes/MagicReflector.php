<?php
namespace figdice\classes;

use ReflectionClass;

class MagicReflector
{
    private static $cache = [];

    /**
     * @param mixed $object The instance on which to invoke the magic getter
     * @param string $getter The name of the getXXX function to invoke on the object
     * @return mixed|null
     */
    public static function invoke($object, $getter)
    {
        $className = get_class($object);

        // First, check our cache for specified class.
        if (! array_key_exists($className, self::$cache)) {
            $getters = [];

            // Parse the docblock for @method declarations
            $reflector = new ReflectionClass($className);
            $docblock = $reflector->getDocComment();
            $lines = explode("\n", $docblock);
            foreach ($lines as $line) {
                // Getters in the Doc Block of the class element, are declared in the shape:
                // @method returntype getAbcXyz()
                // with potentially multi-space everywhere permitted.
                if (preg_match('/^[\\s]*\\*[\\s]*@method[\\s]+[^\\s]+[\\s]+(get[^\\s\\(]+)[\\s]*\\(/', $line, $matches)) {
                    $getters []= $matches[1];
                }
            }

            self::$cache[$className] = $getters;
        }

        // Now if we've found the getter among the @method declarative tags,
        // invoke it.
        if (in_array($getter, self::$cache[$className])) {
            return $object->$getter();
        }

        return null;
    }
}
