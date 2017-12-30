<?php

namespace UnitedCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use UnitedCMS\CoreBundle\Entity\FieldableContent;

class ValidFieldableContentLocaleValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$this->context->getObject() instanceof FieldableContent) {
            throw new InvalidArgumentException(
                'The ValidContentLocaleValidator constraint expects a UnitedCMS\CoreBundle\Entity\FieldableContent object.'
            );
        }

        /**
         * @var FieldableContent $content
         */
        $content = $this->context->getObject();

        // If there is no content type or this content type does not support localization, this field must be empty.
        if (empty($content->getEntity()) || empty($content->getEntity()->getLocales())) {
            if($value != null) {
                $this->context->buildViolation($constraint->message)
                    ->setInvalidValue(null)
                    ->atPath('[locale]')
                    ->addViolation();
            }
            return;
        }

        if($value == null) {
            return;
        }

        if(!in_array($value, $content->getEntity()->getLocales())) {
            $this->context->buildViolation($constraint->message)
                ->setInvalidValue($value)
                ->atPath('[locale]')
                ->addViolation();
        }
    }
}