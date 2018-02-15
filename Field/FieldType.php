<?php

namespace UnitedCMS\CoreBundle\Field;

use GraphQL\Type\Definition\Type;
use Symfony\Component\Validator\ConstraintViolation;
use UnitedCMS\CoreBundle\Entity\FieldableField;
use UnitedCMS\CoreBundle\SchemaType\SchemaTypeManager;

/**
 * An abstract base field type, that allows to easily implement custom field types.
 */
abstract class FieldType implements FieldTypeInterface
{
    /**
     * The unique type identifier for this field type.
     */
    const TYPE = "";

    /**
     * The Symfony form type for this field. Can also be a custom form type.
     */
    const FORM_TYPE = "";

    /**
     * All settings of this field type by key with optional default value.
     */
    const SETTINGS = [];

    /**
     * All required settings for this field type.
     */
    const REQUIRED_SETTINGS = [];

    /**
     * @var FieldableField $field
     */
    protected $field;

    /**
     * {@inheritdoc}
     */
    static function getType(): string {
        return static::TYPE;
    }

    /**
     * {@inheritdoc}
     */
    function getFormType(): string {
        return static::FORM_TYPE;
    }

    /**
     * {@inheritdoc}
     */
    function getFormOptions(): array {
        return [
            'label' => $this->getTitle(),
            'required' => false,
        ];
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLType(SchemaTypeManager $schemaTypeManager, $nestingLevel = 0) {
        return Type::string();
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLInputType(SchemaTypeManager $schemaTypeManager, $nestingLevel = 0) {
        return Type::string();
    }

    /**
     * {@inheritdoc}
     */
    function resolveGraphQLData($value) {
        if (!$this->fieldIsPresent()) {
            return 'undefined';
        }
        return (string)$value;
    }

    /**
     * {@inheritdoc}
     */
    function setEntityField(FieldableField $field) {
        $this->field = $field;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    function unsetEntityField() {
        $this->field = null;
    }

    /**
     * {@inheritdoc}
     */
    public function fieldIsPresent(): bool {
        return !empty($this->field);
    }

    /**
     * {@inheritdoc}
     */
    function getTitle(): string {
        if (!$this->fieldIsPresent()) {
            return 'Undefined';
        }
        return $this->field->getTitle();
    }

    /**
     * {@inheritdoc}
     */
    function getIdentifier(): string {
        if (!$this->fieldIsPresent()) {
            return 'undefined';
        }
        return $this->field->getIdentifier();
    }

    /**
     * Basic settings validation based on self::SETTINGS and self::REQUIRED_SETTINGS constants. More sophisticated
     * validation should be done in child implementations.
     *
     * @param FieldableFieldSettings $settings
     * @return array
     */
    function validateSettings(FieldableFieldSettings $settings): array
    {
        $violations = [];

        if(is_object($settings)) {
            $settings = get_object_vars($settings);
        }

        // Check that only allowed settings are present.
        foreach (array_keys($settings) as $setting) {
            if(!in_array($setting, static::SETTINGS)) {
                $violations[] = new ConstraintViolation(
                    'validation.additional_data',
                    'validation.additional_data',
                    [],
                    $settings,
                    $setting,
                    $settings
                );
            }
        }

        // Check that all required settings are present.
        foreach (static::REQUIRED_SETTINGS as $setting) {
            if(!isset($settings[$setting])) {
                $violations[] = new ConstraintViolation(
                    'validation.required',
                    'validation.required',
                    [],
                    $settings,
                    $setting,
                    $settings
                );
            }
        }

        return $violations;
    }

    /**
     * {@inheritdoc}
     */
    function validateData($data): array {
        return [];
    }

    protected function createViolation($message, $messageTemplate = null, $parameters = [], $root = null, string $propertyPath = null, $invalidValue = null, $plural = null) {

        if(!$messageTemplate) {
            $messageTemplate = $message;
        }

        if(!$propertyPath) {
            $propertyPath = '[' . $this->getIdentifier() . ']';
        }

        return new ConstraintViolation($message, $messageTemplate, $parameters, $root, $propertyPath, $invalidValue, $plural);
    }
}