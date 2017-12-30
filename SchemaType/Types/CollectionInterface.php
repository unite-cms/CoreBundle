<?php

namespace UnitedCMS\CoreBundle\SchemaType\Types;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;
use UnitedCMS\CoreBundle\Entity\ContentInCollection;
use UnitedCMS\CoreBundle\Service\UnitedCMSManager;
use UnitedCMS\CoreBundle\SchemaType\SchemaTypeManager;

class CollectionInterface extends InterfaceType
{

    public function __construct(SchemaTypeManager $schemaTypeManager, UnitedCMSManager $unitedCMSManager)
    {

        parent::__construct(
            [
                'fields' => function () use ($schemaTypeManager) {
                    return [
                        'identifier' => Type::id(),
                        'type' => Type::string(),
                    ];
                },
                'resolveType' => function ($value) use ($schemaTypeManager, $unitedCMSManager) {
                    if (!$value instanceof ContentInCollection) {
                        throw new \InvalidArgumentException(
                            'Value must be instance of '.ContentInCollection::class.'.'
                        );
                    }

                    $type = ucfirst($value->getContent()->getContentType()->getIdentifier()).ucfirst($value->getCollection()->getIdentifier()).'Collection';

                    return $schemaTypeManager->getSchemaType($type, $unitedCMSManager->getDomain());
                },
            ]
        );
    }
}