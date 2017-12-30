<?php

namespace UnitedCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use UnitedCMS\CoreBundle\Collection\CollectionTypeManager;

class CollectionTypeValidator extends ConstraintValidator
{
    /**
     * @var CollectionTypeManager
     */
    private $typeManager;

    public function __construct(CollectionTypeManager $typeManager)
    {
        $this->typeManager = $typeManager;
    }

    public function validate($value, Constraint $constraint)
    {
        if (!is_string($value) || !$this->typeManager->hasCollectionType($value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ string }}', $value)
                ->addViolation();
        }
    }
}