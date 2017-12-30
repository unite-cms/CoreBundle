<?php

namespace UnitedCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use UnitedCMS\CoreBundle\Entity\Content;

class ValidCollectionContentTypeValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if (!$this->context->getObject() instanceof Content) {
            throw new InvalidArgumentException(
                'The ValidCollectionContentTypeValidator constraint expects a UnitedCMS\CoreBundle\Entity\Content object.'
            );
        }

        /**
         * @var Content $content
         */
        $content = $this->context->getObject();

        foreach ($value as $key => $item) {
            if ($item->getCollection()->getContentType() !== $content->getContentType()) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $this->formatValue($item))
                    ->setInvalidValue($item)
                    ->atPath('['.$key.']')
                    ->addViolation();
            }
        }
    }
}