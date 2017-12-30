<?php

namespace UnitedCMS\CoreBundle\Collection\Types;

use UnitedCMS\CoreBundle\Collection\CollectionType;

class TableCollectionType extends CollectionType
{
    const TYPE = "table";
    const TEMPLATE = "UnitedCMSCoreBundle:Collections:Table/index.html.twig";

    const SETTINGS = [
        'columns',
        'sort_field',
        'sort_asc',
    ];

    function getTemplateRenderParameters(string $selectMode = self::SELECT_MODE_NONE): array
    {
        $columns = $this->collection->getSettings()->columns ?? [];
        $sort_field = $this->collection->getSettings()->sort_field ?? 'updated';
        $sort_asc = $this->collection->getSettings()->sort_asc ?? false;

        // If no columns are defined, try to find any human readable key identifier and also add common fields.
        $fields = $this->collection->getContentType()->getFields();
        $possible_field_types = ['text'];

        if (empty($columns)) {
            if ($fields->containsKey('title') && in_array($fields->get('title')->getType(), $possible_field_types)) {
                $columns['title'] = 'Title';
            }

            elseif ($fields->containsKey('name') && in_array($fields->get('name')->getType(), $possible_field_types)) {
                $columns['name'] = 'Name';
            }

            else {
                $columns['id'] = 'ID';
            }

            $columns['created'] = 'Created';
            $columns['updated'] = 'Updated';
        }

        return [
            'sort' => [
                'field' => $sort_field,
                'asc' => $sort_asc,
            ],
            'columns' => $columns,
            'collection' => $this->collection->getIdentifier(),
            'contentType' => $this->collection->getContentType()->getIdentifier(),
        ];
    }
}