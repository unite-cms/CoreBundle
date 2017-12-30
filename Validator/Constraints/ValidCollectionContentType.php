<?php

namespace UnitedCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ValidCollectionContentType extends Constraint
{
    public $message = 'Content and its collections must be in the same contentType';
}