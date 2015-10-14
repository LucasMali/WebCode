<?php
/**
 * Based off of ZendFrameworks implementation
 */
namespace DJ\Api;

/**
 * Class AbstractEntity
 * @package DJ\Api
 */
abstract class AbstractEntity implements \JsonSerializable
{
    /**
     * @param null $entity
     */
    public function __construct($entity = null)
    {
        //loop through the passed in entity elements and set them to the object
        if ($entity !== null && is_array($entity)) {
            //echo get_called_class();
            foreach (array_keys(get_class_vars(get_called_class())) as $key) {
                if (isset($entity[$key])) {
                    $this->$key = $entity[$key];
                }
            }
        }
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $array = array();
        foreach (array_keys(get_class_vars(get_called_class())) as $key) {
            $array[$key] = $this->$key;
        }
        return $array;
    }
}