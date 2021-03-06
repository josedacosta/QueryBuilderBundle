<?php

namespace JD\jQueryQueryBuilderBundle\Services;

use Doctrine\ORM\QueryBuilder;
use \stdClass;

trait jQueryQueryBuilderFunctions
{

    /**
     * @param stdClass $rule
     */
    abstract protected function checkRuleCorrect(stdClass $rule);

    protected $operators = array(
        'equal' => array('accept_values' => true, 'apply_to' => ['string', 'number', 'datetime']),
        'not_equal' => array('accept_values' => true, 'apply_to' => ['string', 'number', 'datetime']),
        'in' => array('accept_values' => true, 'apply_to' => ['string', 'number', 'datetime']),
        'not_in' => array('accept_values' => true, 'apply_to' => ['string', 'number', 'datetime']),
        'less' => array('accept_values' => true, 'apply_to' => ['number', 'datetime']),
        'less_or_equal' => array('accept_values' => true, 'apply_to' => ['number', 'datetime']),
        'greater' => array('accept_values' => true, 'apply_to' => ['number', 'datetime']),
        'greater_or_equal' => array('accept_values' => true, 'apply_to' => ['number', 'datetime']),
        'between' => array('accept_values' => true, 'apply_to' => ['number', 'datetime']),
        'begins_with' => array('accept_values' => true, 'apply_to' => ['string']),
        'not_begins_with' => array('accept_values' => true, 'apply_to' => ['string']),
        'contains' => array('accept_values' => true, 'apply_to' => ['string']),
        'not_contains' => array('accept_values' => true, 'apply_to' => ['string']),
        'ends_with' => array('accept_values' => true, 'apply_to' => ['string']),
        'not_ends_with' => array('accept_values' => true, 'apply_to' => ['string']),
        'is_empty' => array('accept_values' => false, 'apply_to' => ['string']),
        'is_not_empty' => array('accept_values' => false, 'apply_to' => ['string']),
        'is_null' => array('accept_values' => false, 'apply_to' => ['string', 'number', 'datetime']),
        'is_not_null' => array('accept_values' => false, 'apply_to' => ['string', 'number', 'datetime'])
    );

    protected $operator_sql = array(
        'equal' => array('operator' => '='),
        'not_equal' => array('operator' => '!='),
        'in' => array('operator' => 'IN'),
        'not_in' => array('operator' => 'NOT IN'),
        'less' => array('operator' => '<'),
        'less_or_equal' => array('operator' => '<='),
        'greater' => array('operator' => '>'),
        'greater_or_equal' => array('operator' => '>='),
        'between' => array('operator' => 'BETWEEN'),
        'begins_with' => array('operator' => 'LIKE', 'prepend' => '%'),
        'not_begins_with' => array('operator' => 'NOT LIKE', 'prepend' => '%'),
        'contains' => array('operator' => 'LIKE', 'append' => '%', 'prepend' => '%'),
        'not_contains' => array('operator' => 'NOT LIKE', 'append' => '%', 'prepend' => '%'),
        'ends_with' => array('operator' => 'LIKE', 'append' => '%'),
        'not_ends_with' => array('operator' => 'NOT LIKE', 'append' => '%'),
        'is_empty' => array('operator' => '='),
        'is_not_empty' => array('operator' => '!='),
        'is_null' => array('operator' => 'NULL'),
        'is_not_null' => array('operator' => 'NOT NULL')
    );

    protected $needs_array = array(
        'IN', 'NOT IN', 'BETWEEN',
    );

    /**
     * Determine if an operator (LIKE/IN) requires an array.
     *
     * @param $operator
     *
     * @return bool
     */
    protected function operatorRequiresArray($operator)
    {
        return in_array($operator, $this->needs_array);
    }

    /**
     * Determine if an operator is NULL/NOT NULL
     *
     * @param $operator
     *
     * @return bool
     */
    protected function operatorIsNull($operator)
    {
        return ($operator == 'NULL' || $operator == 'NOT NULL') ? true : false;
    }

    /**
     * Make sure that a condition is either 'or' or 'and'.
     *
     * @param $condition
     * @return string
     * @throws \Exception
     */
    protected function validateCondition($condition)
    {
        $condition = trim(strtolower($condition));

        if ($condition !== 'and' && $condition !== 'or') {
            throw new \Exception("Condition can only be one of: 'and', 'or'.");
        }

        return $condition;
    }

    /**
     * Enforce whether the value for a given field is the correct type
     *
     * @param bool $requireArray value must be an array
     * @param mixed $value the value we are checking against
     * @param string $field the field that we are enforcing
     * @return mixed value after enforcement
     * @throws \Exception if value is not a correct type
     */
    protected function enforceArrayOrString($requireArray, $value, $field)
    {
        $this->checkFieldIsAnArray($requireArray, $value, $field);

        if (!$requireArray && is_array($value)) {
            return $this->convertArrayToFlatValue($field, $value);
        }

        return $value;
    }

    /**
     * Ensure that a given field is an array if required.
     *
     * @see enforceArrayOrString
     * @param boolean $requireArray
     * @param $value
     * @param string $field
     * @throws \Exception
     */
    protected function checkFieldIsAnArray($requireArray, $value, $field)
    {
        if ($requireArray && !is_array($value)) {
            throw new \Exception("Field ($field) should be an array, but it isn't.");
        }
    }

    /**
     * Convert an array with just one item to a string.
     *
     * In some instances, and array may be given when we want a string.
     *
     * @see enforceArrayOrString
     * @param string $field
     * @param $value
     * @return mixed
     * @throws \Exception
     */
    protected function convertArrayToFlatValue($field, $value)
    {
        if (count($value) !== 1) {
            throw new \Exception("Field ($field) should not be an array, but it is.");
        }

        return $value[0];
    }

    /**
     * Append or prepend a string to the query if required.
     *
     * @param bool $requireArray value must be an array
     * @param mixed $value the value we are checking against
     * @param mixed $sqlOperator
     * @return mixed $value
     */
    protected function appendOperatorIfRequired($requireArray, $value, $sqlOperator)
    {
        if (!$requireArray) {
            if (isset($sqlOperator['append'])) {
                $value = $sqlOperator['append'] . $value;
            }

            if (isset($sqlOperator['prepend'])) {
                $value = $value . $sqlOperator['prepend'];
            }
        }

        return $value;
    }

    /**
     * Decode the given JSON
     *
     * @param string incoming json
     * @throws \Exception
     * @return stdClass
     */
    private function decodeJSON($json)
    {
        $query = json_decode($json);
        if (json_last_error()) {
            throw new \Exception('JSON parsing threw an error: ' . json_last_error_msg());
        }
        if (!is_object($query)) {
            throw new \Exception('The query is not valid JSON');
        }
        return $query;
    }

    /**
     * get a value for a given rule.
     *
     * throws an exception if the rule is not correct.
     *
     * @param stdClass $rule
     * @throws \Exception
     */
    private function getRuleValue(stdClass $rule)
    {
        if (!$this->checkRuleCorrect($rule)) {
            throw new \Exception('ERROR : checkRuleCorrect !');
        }
        return $rule->value;
    }

    /**
     * Check that a given field is in the allowed list if set.
     *
     * @param $fields
     * @param $field
     * @throws \Exception
     */
    private function ensureFieldIsAllowed($fields, $field)
    {
        if (is_array($fields) && !in_array($field, $fields)) {
            throw new \Exception("Field ({$field}) does not exist in fields list");
        }
    }

    /**
     * makeQuery, for arrays.
     *
     * Some types of SQL Operators (ie, those that deal with lists/arrays) have specific requirements.
     * This function enforces those requirements.
     *
     * @param QueryBuilder $queryBuilder
     * @param stdClass $rule
     * @param array $sqlOperator
     * @param array $value
     * @param string $condition
     *
     * @throws \Exception
     *
     * @return Builder
     */
    protected function makeQueryWhenArray(QueryBuilder $queryBuilder, stdClass $rule, array $sqlOperator, array $value, $condition)
    {
        if ($sqlOperator['operator'] == 'IN' || $sqlOperator['operator'] == 'NOT IN') {
            return $this->makeArrayQueryIn($queryBuilder, $rule, $sqlOperator['operator'], $value, $condition);
        } elseif ($sqlOperator['operator'] == 'BETWEEN') {
            return $this->makeArrayQueryBetween($queryBuilder, $rule, $value, $condition);
        }

        throw new \Exception('makeQueryWhenArray could not return a value');
    }

    /**
     * Create a 'null' query when required.
     *
     * @param QueryBuilder $queryBuilder
     * @param stdClass $rule
     * @param array $sqlOperator
     * @param array $value
     * @param string $condition
     *
     * @return Builder
     */
    protected function makeQueryWhenNull(QueryBuilder $queryBuilder, stdClass $rule, array $sqlOperator, $condition)
    {

        if ($sqlOperator['operator'] == 'NULL') {
            if ($condition === 'and') {
                return $queryBuilder->andWhere($queryBuilder->expr()->isNull($rule->field));
            } else if ($condition === 'or') {
                return $queryBuilder->orWhere($queryBuilder->expr()->isNull($rule->field));
            }
        } elseif ($sqlOperator['operator'] == 'NOT NULL') {
            if ($condition === 'and') {
                return $queryBuilder->andWhere($queryBuilder->expr()->isNotNull($rule->field));
            } else if ($condition === 'or') {
                return $queryBuilder->orWhere($queryBuilder->expr()->isNotNull($rule->field));
            }
        }

        throw new \Exception('makeQueryWhenNull was called on an SQL operator that is not null');
    }

    /**
     * makeArrayQueryIn, when the query is an IN or NOT IN...
     *
     * @see makeQueryWhenArray
     * @param QueryBuilder $queryBuilder
     * @param stdClass $rule
     * @param string $operator
     * @param array $value
     * @param string $condition
     * @return Builder
     */
    private function makeArrayQueryIn(QueryBuilder $queryBuilder, stdClass $rule, $operator, array $value, $condition)
    {

        if ($operator == 'NOT IN') {
            if ($condition === 'and') {
                return $queryBuilder->andWhere($queryBuilder->expr()->notIn($rule->field, $value));
            } else if ($condition === 'or') {
                return $queryBuilder->orWhere($queryBuilder->expr()->notIn($rule->field, $value));
            }
        }

        if ($condition === 'and') {
            return $queryBuilder->andWhere($queryBuilder->expr()->in($rule->field, $value));
        } else if ($condition === 'or') {
            return $queryBuilder->orWhere($queryBuilder->expr()->in($rule->field, $value));
        }

    }


    /**
     * makeArrayQueryBetween, when the query is an IN or NOT IN...
     *
     * @see makeQueryWhenArray
     * @param QueryBuilder $queryBuilder
     * @param stdClass $rule
     * @param array $value
     * @param string $condition
     * @throws \Exception when more then two items given for the between
     * @return Builder
     */
    private function makeArrayQueryBetween(QueryBuilder $queryBuilder, stdClass $rule, array $value, $condition)
    {

        if (count($value) !== 2) {
            throw new \Exception("{$rule->field} should be an array with only two items.");
        }

        if ($condition === 'and') {
            return $queryBuilder->andWhere($queryBuilder->expr()->between($rule->field, $value));
        } else if ($condition === 'or') {
            return $queryBuilder->orWhere($queryBuilder->expr()->between($rule->field, $value));
        }

    }

}
