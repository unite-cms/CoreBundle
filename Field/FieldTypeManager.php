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
        $fieldType->setEntityField($field);
        $constraints = $fieldType->validateData($data);
        $fieldType->unsetEntityField();

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
        $fieldType->setEntityField($field);
        $constraints = $fieldType->validateSettings($settings);
        $fieldType->unsetEntityField();

        return $constraints;
    }

    public function onContentInsert(ContentTypeField $field, Content $content, LifecycleEventArgs $args) {
        $fieldType = $this->getFieldType($field->getType());

        if(method_exists($fieldType, 'onContentInsert')) {
            $fieldType->setEntityField($field);
            $fieldType->onContentInsert($content, $args->getObjectManager()->getRepository('UnitedCMSCoreBundle:Content'), $args);
            $fieldType->unsetEntityField();
        }
    }

    public function onContentUpdate(ContentTypeField $field, Content $content, PreUpdateEventArgs $args) {
        $fieldType = $this->getFieldType($field->getType());
        if(method_exists($fieldType, 'onContentUpdate')) {
            $fieldType->setEntityField($field);
            $fieldType->onContentUpdate($content, $args->getObjectManager()->getRepository('UnitedCMSCoreBundle:Content'), $args);
            $fieldType->unsetEntityField();
        }
    }

    public function onContentRemove(ContentTypeField $field, Content $content, LifecycleEventArgs $args) {
        $fieldType = $this->getFieldType($field->getType());
        if(method_exists($fieldType, 'onContentRemove')) {
            $fieldType->setEntityField($field);
            $fieldType->onContentRemove($content, $args->getObjectManager()->getRepository('UnitedCMSCoreBundle:Content'), $args);
            $fieldType->unsetEntityField();
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