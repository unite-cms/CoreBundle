<?php

namespace UnitedCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use UnitedCMS\CoreBundle\Entity\Content;

class ValidContentTranslationOfValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$this->context->getObject() instanceof Content) {
            throw new InvalidArgumentException(
                'The ValidContentLocaleValidator constraint expects a UnitedCMS\CoreBundle\Entity\Content object.'
            );
        }

        if($value == null) {
            return;
        }

        /**
         * @var Content $content
         */
        $content = $this->context->getObject();

        if($value->getLocale() === $content->getLocale()) {
            $this->context->buildViolation($constraint->uniqueLocaleMessage)
                ->setInvalidValue(null)
                ->atPath('[translationOf]')
                ->addViolation();
        }
    }
}