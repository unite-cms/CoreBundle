<?php

namespace UnitedCMS\CoreBundle\View\Types;

use UnitedCMS\CoreBundle\View\ViewType;

class SortableViewType extends ViewType
{
    const TYPE = "sortable";
    const TEMPLATE = "UnitedCMSCoreBundle:Views:Sortable/index.html.twig";

    const SETTINGS = [
        'columns',
        'sort_field',
    ];

    const REQUIRED_SETTINGS = [
        'sort_field',
    ];

    function getTemplateRenderParameters(string $selectMode = self::SELECT_MODE_NONE): array
    {
        $columns = $this->view->getSettings()->columns ?? [];
        $sort_field = $this->view->getSettings()->sort_field;

        // If no columns are defined, try to find any human readable key identifier and also add common fields.
        $fields = $this->view->getContentType()->getFields();
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
            'sort_field' => $sort_field,
            'columns' => $columns,
            'View' => $this->view->getIdentifier(),
            'contentType' => $this->view->getContentType()->getIdentifier(),
        ];
    }
}