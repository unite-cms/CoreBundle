<?php

namespace UnitedCMS\CoreBundle\Field;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\Validator\ConstraintViolation;
use UnitedCMS\CoreBundle\Entity\Content;
use UnitedCMS\CoreBundle\Entity\ContentTypeField;
use UnitedCMS\CoreBundle\Entity\FieldableField;

class FieldTypeManager
{
    /**
     * @var FieldTypeInterface[]
     */
    private $fieldTypes = [];

    /**
     * @return FieldTypeInterface[]
     */
    public function getFieldTypes(): array
    {
        return $this->fieldTypes;
    }

    public function hasFieldType($key): bool
    {
        return array_key_exists($key, $this->fieldTypes);
    }

    public function getFieldType($key): FieldTypeInterface
    {
        if (!$this->hasFieldType($key)) {
            throw new \InvalidArgumentException("The field type: '$key' was not found.");
        }

        return $this->fieldTypes[$key];
    }

    /**
     * Validates content data for given field by using the validation method of the field type.
     * @param FieldableField $field
     * @param mixed $data
     *
     * @return ConstraintViolation[]
     */
    public function validateFieldData(FieldableField $field, $data): array
    {
        $fieldType = $this->getFieldType($field->getType());
        $constraints = $fieldType->validateData($field, $data);
        return $constraints;
    }

    /**
     * Validates field settings for given field by using the validation method of the field type.
     * @param FieldableField $field
     * @param FieldableFieldSettings $settings
     *
     * @return ConstraintViolation[]
     */
    public function validateFieldSettings(FieldableField $field, FieldableFieldSettings $settings): array
    {
        $fieldType = $this->getFieldType($field->getType());
        $constraints = $fieldType->validateSettings($field,$settings);
        return $constraints;
    }

    public function onContentInsert(ContentTypeField $field, Content $content, LifecycleEventArgs $args) {
        $fieldType = $this->getFieldType($field->getType());

        if(method_exists($fieldType, 'onContentInsert')) {
            $fieldType->onContentInsert($field, $content, $args->getObjectManager()->getRepository('UnitedCMSCoreBundle:Content'), $args);
        }
    }

    public function onContentUpdate(ContentTypeField $field, Content $content, PreUpdateEventArgs $args) {
        $fieldType = $this->getFieldType($field->getType());
        if(method_exists($fieldType, 'onContentUpdate')) {
            $fieldType->onContentUpdate($field, $content, $args->getObjectManager()->getRepository('UnitedCMSCoreBundle:Content'), $args);
        }
    }

    public function onContentRemove(ContentTypeField $field, Content $content, LifecycleEventArgs $args) {
        $fieldType = $this->getFieldType($field->getType());
        if(method_exists($fieldType, 'onContentRemove')) {
            $fieldType->onContentRemove($field, $content, $args->getObjectManager()->getRepository('UnitedCMSCoreBundle:Content'), $args);
        }
    }

    /**
     * @param FieldTypeInterface $fieldType
     *
     * @return FieldTypeManager
     */
    public function registerFieldType(FieldTypeInterface $fieldType)
    {
        if (!isset($this->fieldTypes[$fieldType::getType()])) {
            $this->fieldTypes[$fieldType::getType()] = $fieldType;
        }

        return $this;
    }
}