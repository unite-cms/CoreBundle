<?php

namespace UnitedCMS\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolver;
use UnitedCMS\CoreBundle\Field\Types\TextFieldType;

class FieldableFormType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Handle content locales
        if(!empty($options['locales'])) {

            // if this fieldable has exactly one possible locale, add it as hidden field.
            if(count($options['locales']) == 1) {
                $builder->add('locale', HiddenType::class, ['data' => $options['locales'][0]]);

            // if this fieldable has more than one possible locale, render a selection list.
            } else {
                $choices = [];
                foreach($options['locales'] as $locale) {
                    $choices[Intl::getLocaleBundle()->getLocaleName($locale)] = $locale;
                }
                $builder->add('locale', ChoiceType::class, ['choices' => $choices]);
            }
        }

        /**
         * @var FieldableFormField $field
         */
        foreach ($options['fields'] as $field) {
            $field->getFieldType()->setEntityField($field->getFieldDefinition());
            $builder->add(
                $field->getFieldType()->getIdentifier(),
                $field->getFieldType()->getFormType(),
                $field->getFieldType()->getFormOptions()
            );

            // Allow the field type to add a data transformer.
            if($transformer = $field->getFieldType()->getDataTransformer()) {
                $builder->get($field->getFieldType()->getIdentifier())->addModelTransformer($transformer);
            }

            $field->getFieldType()->unsetEntityField();
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('fields');
        $resolver->setDefined('locales');
    }
}