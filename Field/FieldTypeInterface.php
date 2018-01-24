<?php

namespace UnitedCMS\CoreBundle\Field;

use GraphQL\Type\Definition\Type;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use UnitedCMS\CoreBundle\Entity\FieldableField;
use UnitedCMS\CoreBundle\SchemaType\SchemaTypeManager;

interface FieldTypeInterface
{
    static function getType(): string;

    /**
     * @param SchemaTypeManager $schemaTypeManager
     * @param int $nestingLevel
     * @return Type
     */
    function getGraphQLType(SchemaTypeManager $schemaTypeManager, $nestingLevel = 0);

    /**
     * @param SchemaTypeManager $schemaTypeManager
     * @param int $nestingLevel
     * @return Type
     */
    function getGraphQLInputType(SchemaTypeManager $schemaTypeManager, $nestingLevel = 0);

    function getFormType(): string;

    function getFormOptions(): array;

    function getIdentifier(): string;

    /**
     * @return DataTransformerInterface
     */
    function getDataTransformer();

    function resolveGraphQLData($value);

    function setEntityField(FieldableField $field);

    function unsetEntityField();

    /**
     * @param $data
     * @return ConstraintViolation[]
     */
    function validateData($data): array;

    /**
     * @param FieldableFieldSettings $settings
     * @return ConstraintViolation[]
     */
    function validateSettings(FieldableFieldSettings $settings): array;
}