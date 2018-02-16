<?php

namespace UnitedCMS\CoreBundle\Field;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\Validator\ConstraintViolation;
use UnitedCMS\CoreBundle\Entity\Content;
use UnitedCMS\CoreBundle\Entity\ContentTypeField;
use UnitedCMS\CoreBundle\Entity\FieldableField;
use UnitedCMS\CoreBundle\Entity\Setting;
use UnitedCMS\CoreBundle\Entity\SettingTypeField;

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
        if(method_exists($fieldType, 'onCreate')) {
            $data = $content->getData();
            $fieldType->onCreate($field, $content, $args->getObjectManager()->getRepository('UnitedCMSCoreBundle:Content'), $data);
            $content->setData($data);
        }
    }

    public function onContentUpdate(ContentTypeField $field, Content $content, PreUpdateEventArgs $args) {
        $fieldType = $this->getFieldType($field->getType());
        if(method_exists($fieldType, 'onUpdate')) {
            $data = $content->getData();
            $old_data = $args->hasChangedField('data') ? $args->getOldValue('data') : [];
            $fieldType->onUpdate($field, $content, $args->getObjectManager()->getRepository('UnitedCMSCoreBundle:Content'), $old_data, $data);
            $content->setData($data);
        }
    }

    public function onSettingUpdate(SettingTypeField $field, Setting $setting, PreUpdateEventArgs $args) {
        $fieldType = $this->getFieldType($field->getType());
        if(method_exists($fieldType, 'onUpdate')) {
            $data = $setting->getData();
            $fieldType->onUpdate($field, $setting, $args->getObjectManager()->getRepository('UnitedCMSCoreBundle:Setting'), $args->getOldValue('data'), $data);
            $setting->setData($data);
        }
    }

    public function onContentRemove(ContentTypeField $field, Content $content, LifecycleEventArgs $args) {
        $fieldType = $this->getFieldType($field->getType());

        if(method_exists($fieldType, 'onSoftDelete') && !$content->getDeleted()) {
            $fieldType->onSoftDelete($field, $content, $args->getObjectManager()->getRepository('UnitedCMSCoreBundle:Content'), $content->getData());
        }

        if(method_exists($fieldType, 'onHardDelete') && $content->getDeleted()) {
            $fieldType->onHardDelete($field, $content, $args->getObjectManager()->getRepository('UnitedCMSCoreBundle:Content'), $content->getData());
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