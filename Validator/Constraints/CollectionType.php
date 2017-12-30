<?php

namespace UnitedCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class CollectionType extends Constraint
{
    public $message = 'This type is not a registered collection type.';
}