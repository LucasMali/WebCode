<?php
/**
 *  LEGACY REFACTORING INTO OOP
 */
namespace DJ\Api;

use DJ\Exception\LogicException;
use Zend\Db\ResultSet\ResultSet;

/**
 * Abstract API
 *
 * Foundation for the  API standard functionality.
 *
 *
 * @package DJ\Api
 * @author Team Awesome!
 * @api
 */
abstract class AbstractApi
{
    public function post(AbstractEntity $entity)
    {//create
        throw new LogicException('The method post has not been implemented for this API.');
    }

    public function put(AbstractEntity $entity)
    {//update
        throw new LogicException('The method put has not been implemented for this API.');
    }

    public function get($id)
    {
        throw new LogicException('The method get has not been implemented for this API.');
    }

    public function getAll()
    {
        throw new LogicException('The method getAll has not been implemented for this API.');
    }

    public function delete($id)
    {
        throw new LogicException('The method delete has not been implemented for this API.');
    }


    /**
     * Generate Conditions
     * @param array $conditions
     * @return string
     */
    protected function generate_conditions($conditions)
    {
        $itterator = 0;
        $sql = ' WHERE ';

        // build off conditions given
        foreach ($conditions as $key => $value) {
            if (!is_numeric($value)) {
                $value = '\'' . $value . '\'';
            }

            $sql .= $key . ' = ' . $value;
            if (++$itterator < count($conditions)) {
                $sql .= ' AND ';
            }
        }

        return $sql;
    }// end generate_query

    /**
     *
     * @param array $identifiers
     * @return string
     */
    protected function generate_identifiers($identifiers)
    {
        $itterator = 0;
        $sql = '';

        // build off conditions given
        foreach ($identifiers as $value) {
            $sql .= $value;
            if (++$itterator < count($identifiers)) {
                $sql .= ',';
            }
        }

        return $sql;
    }// end generate_query

    /**
     * Maps the keys of the array to the entity appropriate versions
     *
     * @param array $array An array of incoming values
     * @return array A modified value array that maps keys to the entity values
     */
    protected function fixKeys($array, $keyMap = [])
    {
        $return = array();
        if ($keyMap === []) {
            $keyMap = $this->keyMap;
        }
        foreach ($keyMap as $k => $v) {
            if ($array[$k] != null) { // prevent overriding a populated value
                $return[$v] = $array[$k];
            }
        }
        return $return;
    }

    /**
     * Takes a result set and returns an array of projectEntities
     *
     * @param ResultSet $resultSet
     * @return object Entity
     */
    protected function buildEntities(ResultSet $resultSet, $entity)
    {
        $return = array();
        if ($resultSet->count() > 0) {
            foreach ($resultSet as $row) {
                $return[] = $this->hydrator->hydrate($this->fixKeys($row), $entity);
            }
        }
        return $return;
    }
}

/**
 * Class ConditionValidation
 * @package DJ\Api
 */
abstract class ConditionValidation extends AbstractApi
{
    public $int = ['id', 'offset', 'limit', 'year'];
    public $array = ['sort', 'search'];
}