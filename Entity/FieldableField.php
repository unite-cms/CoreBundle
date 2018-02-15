<?php

namespace UnitedCMS\CoreBundle\Entity;
use UnitedCMS\CoreBundle\Field\FieldableFieldSettings;

/**
 * Defines a fieldable entity.
 */
interface FieldableField
{

    /**
     * @return Fieldable
     */
    public function getEntity();

    /**
     * @param Fieldable $entity
     *
     * @return FieldableField
     */
    public function setEntity($entity);

    /**
     * @return string
     */
    public function getType();

    /**
     * @return string
     */
    public function getIdentifier();

    /**
     * @return string
     */
    public function getTitle();

    /**
     * @return null|FieldableFieldSettings
     */
    public function getSettings();
}