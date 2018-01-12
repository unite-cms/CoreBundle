<?php

namespace UnitedCMS\CoreBundle\Field;

use GraphQL\Type\Definition\Type;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use UnitedCMS\CoreBundle\Entity\FieldableField;
use UnitedCMS\CoreBundle\SchemaType\SchemaTypeManager;

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

    static function getType(): string
    {
        return static::TYPE;
    }

    function getFormType(): string
    {
        return static::FORM_TYPE;
    }

    function getDataTransformer() {
        return null;
    }

    function getFormOptions(): array
    {
        return [
            'label' => $this->getTitle(),
            'required' => false,
        ];
    }

    function setEntityField(FieldableField $field)
    {
        $this->field = $field;

        return $this;
    }

    function unsetEntityField()
    {
        $this->field = null;
    }

    public function fieldIsPresent(): bool
    {
        return !empty($this->field);
    }

    function getTitle(): string
    {
        if (!$this->fieldIsPresent()) {
            return 'Undefined';
        }

        return $this->field->getTitle();
    }

    function getIdentifier(): string
    {
        if (!$this->fieldIsPresent()) {
            return 'undefined';
        }

        return $this->field->getIdentifier();
    }

    function getGraphQLType(SchemaTypeManager $schemaTypeManager, $nestingLevel = 0)
    {
        return Type::string();
    }

    function resolveGraphQLData($value)
    {
        if (!$this->fieldIsPresent()) {
            return 'undefined';
        }

        return (string)$value;
    }

    function validateData($data): array
    {
        return [];
    }

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
}