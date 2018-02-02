<?php

namespace UnitedCMS\CoreBundle\View;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\Validator\ConstraintViolation;
use UnitedCMS\CoreBundle\Entity\View;
use UnitedCMS\CoreBundle\SchemaType\SchemaTypeManager;

interface ViewTypeInterface
{

    const SELECT_MODE_NONE = 'SELECT_MODE_NONE';
    const SELECT_MODE_SINGLE = 'SELECT_MODE_SINGLE';

    // TODO: This is not implemented yet.
    //const SELECT_MODE_MULTIPLE = 'SELECT_MODE_MULTIPLE';

    static function getType(): string;

    static function getTemplate(): string;

    function setEntity(View $view);

    function unsetEntity();

    /**
     * @param string $selectMode, the select mode. Default's to none. But can also be single or multiple.
     *
     * @return array
     */
    function getTemplateRenderParameters(string $selectMode = self::SELECT_MODE_NONE): array;

    /**
     * @param ViewSettings $settings
     *
     * @return ConstraintViolation[]
     */
    function validateSettings(ViewSettings $settings) : array;

    /**
     * @param SchemaTypeManager $schemaTypeManager
     *
     * @return array
     */
    function getMutationSchemaTypes(SchemaTypeManager $schemaTypeManager) : array;

    /**
     * Resolves the value for a mutation action.
     *
     * @param $action
     * @param $value
     * @param array $args
     * @param $context
     * @param ResolveInfo $info
     *
     * @return mixed
     */
    function resolveMutationSchemaType($action, $value, array $args, $context, ResolveInfo $info);
}