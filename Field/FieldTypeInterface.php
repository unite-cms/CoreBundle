<?php

namespace UnitedCMS\CoreBundle\Field;

use GraphQL\Type\Definition\Type;
use Symfony\Component\Validator\ConstraintViolation;
use UnitedCMS\CoreBundle\Entity\FieldableField;
use UnitedCMS\CoreBundle\SchemaType\SchemaTypeManager;

interface FieldTypeInterface
{
    static function getType(): string;

    /**
     * Returns the graphQL schema type for queries. This method must either return a ScalarType or a registered custom
     * type from schemaTypeManager.
     *
     * Example 1:
     *   return GraphQL\Type\Definition\Type::string();
     *
     * Example 2:
     *   return $schemaTypeManager->getSchemaType('ReferenceFieldType', $this->unitedCMSManager->getDomain(), $nestingLevel);
     *
     * @param SchemaTypeManager $schemaTypeManager
     * @param int $nestingLevel
     * @return Type
     */
    function getGraphQLType(SchemaTypeManager $schemaTypeManager, $nestingLevel = 0);

    /**
     * Returns the graphQL schema type for mutation inputs. This method must either return a ScalarType or a registered
     * custom type from schemaTypeManager.
     *
     * Example 1:
     *   return GraphQL\Type\Definition\Type::string();
     *
     * Example 2:
     *   return $schemaTypeManager->getSchemaType('ReferenceFieldTypeInput', $this->unitedCMSManager->getDomain(), $nestingLevel);
     *
     * @param SchemaTypeManager $schemaTypeManager
     * @param int $nestingLevel
     * @return Type
     */
    function getGraphQLInputType(SchemaTypeManager $schemaTypeManager, $nestingLevel = 0);

    /**
     * Returns the class name of the form, used to process data during graphQL mutations and for admin form rendering.
     *
     * @return string
     */
    function getFormType(): string;

    /**
     * Returns options that get passed to the form.
     *
     * @return array
     */
    function getFormOptions(): array;

    /**
     * Get the title for this field.
     *
     * @return string
     */
    function getTitle(): string;

    /**
     * Get the identifier for this field.
     *
     * @return string
     */
    function getIdentifier(): string;

    /**
     * Callback for resolving data for the graphQL API. A simple solution would be to just return the value.
     *
     * @param $value
     * @return mixed
     */
    function resolveGraphQLData($value);

    /**
     * Attach an entity to this field type. This is done just before the public member functions get called. So an
     * entity is available in this methods.
     *
     * @param FieldableField $field
     * @return mixed
     */
    function setEntityField(FieldableField $field);

    /**
     * Detach the entity from this field type. This is done directly after the public member functions get called.
     *
     * @return mixed
     */
    function unsetEntityField();

    /**
     * A callback to allow the field type to validate the field settings.
     *
     * @param FieldableFieldSettings $settings
     * @return ConstraintViolation[]
     */
    function validateSettings(FieldableFieldSettings $settings): array;

    /**
     * A callback to allow the field type to validate the data for a given fieldable.
     *
     * @param array $data
     * @return ConstraintViolation[]
     */
    function validateData($data): array;
}