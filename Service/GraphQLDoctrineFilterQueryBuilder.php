<?php

namespace UnitedCMS\CoreBundle\Service;

use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Orx;

class GraphQLDoctrineFilterQueryBuilder
{
    private $contentEntityFields;
    private $contentEntityPrefix;

    private $filter = null;
    private $parameters = [];
    private $parameterCount = 0;

    public function __construct(array $filterInput, $contentEntityFields = [], $contentEntityPrefix)
    {
        $this->contentEntityFields = $contentEntityFields;
        $this->contentEntityPrefix = $contentEntityPrefix;
        $this->filter = $this->getQueryBuilderComposite($filterInput);
    }

    public function getFilter() {
        return $this->filter;
    }

    public function getParameters() {
        return $this->parameters;
    }

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


    }

}