<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 13.02.18
 * Time: 16:30
 */

namespace UnitedCMS\CoreBundle\Entity;


interface NestableFieldable extends Fieldable
{
    /**
     * @return null|Fieldable|NestableFieldable
     */
    public function getParentEntity();

    /**
     * @return string
     */
    public function getIdentifierPath();
}