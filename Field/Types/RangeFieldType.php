<?php

namespace UnitedCMS\CoreBundle\Field\Types;

use Symfony\Component\Form\Extension\Core\Type\RangeType;
use UnitedCMS\CoreBundle\Field\FieldType;

class RangeFieldType extends FieldType
{
    const TYPE = "range";
    const FORM_TYPE = RangeType::class;

    /**
     * All settings of this field type by key with optional default value.
     */
    const SETTINGS = [ 'min', 'max', 'step' ];

    function getFormOptions(): array
    {
        return array_merge(parent::getFormOptions(), [
            'attr' => [
                'min' => $this->field->getSettings()->min ?? 0,
                'max' => $this->field->getSettings()->max ?? 100,
                'step' => $this->field->getSettings()->step ?? 1
            ],
        ]);
    }
}