<?php

namespace UnitedCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use UnitedCMS\CoreBundle\Collection\CollectionSettings;
use UnitedCMS\CoreBundle\Collection\CollectionTypeManager;
use UnitedCMS\CoreBundle\Entity\Collection;

class ValidCollectionSettingsValidator extends ConstraintValidator
{
    /**
     * @var CollectionTypeManager
     */
    private $collectionTypeManager;

    public function __construct(CollectionTypeManager $collectionTypeManager)
    {
        $this->collectionTypeManager = $collectionTypeManager;
    }

    /**
     * Adds a new ConstraintViolation to the current context. Takes the violation and only modify the propertyPath to
     * make the violation a child of this field.
     *
     * @param ConstraintViolation $violation
     */
    private function addDataViolation(ConstraintViolation $violation)
    {
        $this->context->getViolations()->add(
            new ConstraintViolation(
                $violation->getMessage(),
                $violation->getMessageTemplate(),
                $violation->getParameters(),
                $violation->getRoot(),
                $this->context->getPropertyPath($violation->getPropertyPath()),
                $violation->getInvalidValue(),
                $violation->getPlural(),
                $violation->getCode(),
                $violation->getConstraint()
            )
        );
    }

    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof CollectionSettings) {
            throw new InvalidArgumentException(
                'The ValidCollectionSettingsValidator constraint expects a UnitedCMS\CoreBundle\Collection\CollectionSettings value.'
            );
        }

        if (!$this->context->getObject() instanceof Collection) {
            throw new InvalidArgumentException(
                'The ValidCollectionSettingsValidator constraint expects a UnitedCMS\CoreBundle\Entity\Collection object.'
            );
        }

        if($this->collectionTypeManager->hasCollectionType($this->context->getObject()->getType())) {
            foreach ($this->collectionTypeManager->validateCollectionSettings(
                $this->context->getObject(),
                $value
            ) as $violation) {
                $this->addDataViolation($violation);
            }
        }
    }
}