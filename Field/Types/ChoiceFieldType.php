<?php

namespace UnitedCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use UnitedCMS\CoreBundle\Field\FieldType;

class ChoiceFieldType extends FieldType
{
    const TYPE = "choice";
    const FORM_TYPE = ChoiceType::class;

    /**
     * All settings of this field type by key with optional default value.
     */
    const SETTINGS = [ 'choices' ];

    /**
     * All required settings for this field type.
     */
    const REQUIRED_SETTINGS = [ 'choices' ];

    function getFormOptions(): array
    {
        return array_merge(parent::getFormOptions(), [
            'choices' => $this->field->getSettings()->choices,
        ]);
    }
}