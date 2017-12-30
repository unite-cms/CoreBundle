<?php

namespace UnitedCMS\CoreBundle\SchemaType\Types\Input;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use UnitedCMS\CoreBundle\Entity\Collection;

class ContentTypeCollectionInputType extends InputObjectType
{
    public function __construct()
    {
        parent::__construct(
            [
                'fields' => [
                    'type' => [
                        'type' => Type::string(),
                        'description' => 'Pass the content type identifier',
                    ],
                    'collection' => [
                        'defaultValue' => Collection::DEFAULT_COLLECTION_IDENTIFIER,
                        'type' => Type::string(),
                        'description' => 'Pass the collection identifier. Default is '.Collection::DEFAULT_COLLECTION_IDENTIFIER.'.',
                    ],
                ],
            ]
        );
    }
}