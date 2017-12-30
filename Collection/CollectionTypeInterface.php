<?php

namespace UnitedCMS\CoreBundle\Collection;

use Symfony\Component\Validator\ConstraintViolation;
use UnitedCMS\CoreBundle\Entity\Collection;

interface CollectionTypeInterface
{

    const SELECT_MODE_NONE = 'SELECT_MODE_NONE';
    const SELECT_MODE_SINGLE = 'SELECT_MODE_SINGLE';

    // TODO: This is not implemented yet.
    //const SELECT_MODE_MULTIPLE = 'SELECT_MODE_MULTIPLE';

    static function getType(): string;

    static function getTemplate(): string;

    function setCollection(Collection $collection);

    function unsetCollection();

    /**
     * @param string $selectMode, the select mode. Default's to none. But can also be single or multiple.
     *
     * @return array
     */
    function getTemplateRenderParameters(string $selectMode = self::SELECT_MODE_NONE): array;

    /**
     * @param CollectionSettings $settings
     * @return ConstraintViolation[]
     */
    function validateSettings(CollectionSettings $settings) : array;
}