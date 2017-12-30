<?php

namespace UnitedCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use UnitedCMS\CoreBundle\Entity\Content;

class ValidContentTranslationsValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$this->context->getObject() instanceof Content) {
            throw new InvalidArgumentException(
                'The ValidContentLocaleValidator constraint expects a UnitedCMS\CoreBundle\Entity\Content object.'
            );
        }

        /**
         * @var Content $content
         */
        $content = $this->context->getObject();

        // There cannot be a duplicated locale in the translations.
        $found_locales = $value->map(function(Content $content){ return $content->getLocale(); })->toArray();
        $found_locales[] = $content->getLocale();

        if(count(array_unique($found_locales)) < count($found_locales)) {
            $this->context->buildViolation($constraint->uniqueLocaleMessage)
                ->setInvalidValue(null)
                ->atPath('[translations]')
                ->addViolation();
        }



        // Translations cannot have other translations and their translationOf must be this content.
        foreach($value as $translation) {
            if($translation->getTranslations()->count() > 0 || $translation->getTranslationOf() != $content) {
                $this->context->buildViolation($constraint->nestedTranslationMessage)
                    ->setInvalidValue(null)
                    ->atPath('[translations]')
                    ->addViolation();
            }
        }
    }
}