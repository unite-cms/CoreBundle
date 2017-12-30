<?php

namespace UnitedCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class DefaultCollectionType extends Constraint
{
    public $message = 'The default collection type is missing';
}