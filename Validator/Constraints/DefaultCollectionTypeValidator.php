<?php

namespace UnitedCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use UnitedCMS\CoreBundle\Entity\Collection;
use UnitedCMS\CoreBundle\Entity\ContentInCollection;

class DefaultCollectionTypeValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        $found = false;
        foreach ($value as $item) {

            if ($item instanceof ContentInCollection && $item->getCollection()->getIdentifier(
                ) == Collection::DEFAULT_COLLECTION_IDENTIFIER) {
                $found = true;
            }

            if ($item instanceof Collection && $item->getIdentifier() == Collection::DEFAULT_COLLECTION_IDENTIFIER) {
                $found = true;
            }
        }

        if (!$found) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->addViolation();
        }
    }
}