<?php

namespace UnitedCMS\CoreBundle\Service;

use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Orx;

/**
 * Builds an doctrine query expression by evaluating a nested filter array:
 *
 * Examples:
 *
 * $filterInput = [ 'field' => 'id', 'operator' => '=', 'value' => 123 ];
 * $filterInput = [ 'AND' => [
 *   [ 'field' => 'id', 'operator' => '=', 'value' => 123 ],
 *   [ 'field' => 'id', 'operator' => '>', 'value' => 200 ]
 * ];
 * $filterInput = [ 'AND' => [
 *   'OR' => [
 *      ['field' => 'title', 'operator' => 'LIKE', 'value' => '%foo%'],
 *      ['field' => 'title', 'operator' => 'LIKE', 'value' => '%baa%'],
 *   ],
 *   [ 'field' => 'id', 'operator' => '=', 'value' => 123 ],
 * ]
 */
class GraphQLDoctrineFilterQueryBuilder
{
    private $contentEntityFields;
    private $contentEntityPrefix;

    private $filter = null;
    private $parameters = [];
    private $parameterCount = 0;

    /**
     * GraphQLDoctrineFilterQueryBuilder constructor.
     * @param array $filterInput
     * @param array $contentEntityFields
     * @param string $contentEntityPrefix
     */
    public function __construct(array $filterInput, $contentEntityFields = [], $contentEntityPrefix)
    {
        $this->contentEntityFields = $contentEntityFields;
        $this->contentEntityPrefix = $contentEntityPrefix;
        $this->filter = $this->getQueryBuilderComposite($filterInput);
    }

    /**
     * Returns the doctrine filter object.
     *
     * @return Comparison|Orx|null
     */
    public function getFilter() {
        return $this->filter;
    }

    /**
     * Returns all parameters, that where used in any filter.
     *
     * @return string[]
     */
    public function getParameters() {
        return $this->parameters;
    }

    /**
     * Build the nested doctrine filter object.
     *
     * @param array $filterInput
     * @return Andx|Comparison|Orx
     */
    private function getQueryBuilderComposite(array $filterInput) {

        // filterInput can contain AND, OR or a direct expression

        if(!empty($filterInput['AND'])) {

            $filters = [];
            foreach($filterInput['AND'] as $filter) {
                $filters[] = $this->getQueryBuilderComposite($filter);
            }
            return new Andx($filters);
        }

        else if(!empty($filterInput['OR'])) {

            $filters = [];
            foreach($filterInput['OR'] as $filter) {
                $filters[] = $this->getQueryBuilderComposite($filter);
            }
            return new Orx($filters);
        }

        else if(!empty($filterInput['operator']) && !empty($filterInput['field']) && !empty($filterInput['value'])) {
            $this->parameterCount++;
            $parameter_name = 'graphql_filter_builder_parameter' . $this->parameterCount;
            $this->parameters[$parameter_name] = $filterInput['value'];

            // if we filter by a content field.
            if (in_array($filterInput['field'], $this->contentEntityFields)) {
                $leftSide = $this->contentEntityPrefix . '.' . $filterInput['field'];

                // if we filter by a nested content data field.
            } else {
                $leftSide = "JSON_EXTRACT(" . $this->contentEntityPrefix . ".data, '$." . $filterInput['field'] . "')";
            }

            return new Comparison($leftSide, $filterInput['operator'], ':' . $parameter_name);
        }

        return null;
    }

}