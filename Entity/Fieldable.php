<?php

namespace UnitedCMS\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Defines a fieldable entity.
 */
interface Fieldable
{

    const RESERVED_IDENTIFIERS = ['create', 'view', 'update', 'delete'];

    /**
     * @return FieldableField[]|ArrayCollection
     */
    public function getFields();

    /**
     * @param ArrayCollection|FieldableField[] $fields
     *
     * @return Fieldable
     */
    public function setFields($fields);

    /**
     * @param FieldableField $field
     *
     * @return Fieldable
     */
    public function addField(FieldableField $field);

    /**
     * @return array
     */
    public function getLocales() : array;
}