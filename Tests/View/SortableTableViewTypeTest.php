<?php

namespace UnitedCMS\CoreBundle\Tests\View;

use UnitedCMS\CoreBundle\Entity\ContentTypeField;
use UnitedCMS\CoreBundle\View\ViewSettings;
use UnitedCMS\CoreBundle\Entity\View;
use UnitedCMS\CoreBundle\Entity\ContentType;
use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\Entity\Organization;
use UnitedCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class SortableTableViewTypeTest extends DatabaseAwareTestCase
{

    /**
     * @return View
     */
    private function createInstance() {
        $view = new View();
        $view
            ->setType('sortable')
            ->setTitle('New View')
            ->setIdentifier('new_view')
            ->setSettings(new ViewSettings(['sort_field' => 'position']))
            ->setContentType(new ContentType())
            ->getContentType()
            ->setTitle('ct')
            ->setIdentifier('ct')
            ->setDomain(new Domain())
            ->getDomain()
            ->setTitle('D1')
            ->setIdentifier('d1')
            ->setOrganization(new Organization())
            ->getOrganization()
            ->setTitle('O1')
            ->setIdentifier('o1');

        $field = new ContentTypeField();
        $field->setType('text')->setIdentifier('position')->setTitle('Position');
        $view->getContentType()->addField($field);

        return $view;
    }

    public function testSortableViewWithPositionSetting() {
        $view = $this->createInstance();

        // View should be valid.
        $this->assertCount(0, $this->container->get('validator')->validate($view));

        // Test templateRenderParameters.
        $parameters = $this->container->get('united.cms.view_type_manager')->getTemplateRenderParameters($view);
        $this->assertTrue($parameters->isSelectModeNone());
        $this->assertEquals([
            'created' => 'Created',
            'updated' => 'Updated',
            'id' => 'ID',
        ],$parameters->get('columns'));
        $this->assertEquals('position', $parameters->get('sort_field'));
    }

    public function testSortableViewWithInvalidSettings() {
        $view = $this->createInstance();
        $view->setSettings(new ViewSettings());

        // View should not be valid.
        $errors = $this->container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('validation.required', $errors->get(0)->getMessage());

        $view->setSettings(new ViewSettings([
            'sort_field' => 'position',
            'foo' => 'baa',
        ]));

        // View should not be valid.
        $errors = $this->container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('validation.additional_data', $errors->get(0)->getMessage());
    }

    public function testSortableViewWithValidSettings() {
        $view = $this->createInstance();
        $view->setSettings(new ViewSettings([
            'columns' => [
                'title' => 'Title',
                'foo' => 'baa',
            ],
            'sort_field' => 'position',
        ]));

        $field = new ContentTypeField();
        $field->setType('text')->setIdentifier('position')->setTitle('Position');
        $view->getContentType()->addField($field);

        // View should be valid.
        $this->assertCount(0, $this->container->get('validator')->validate($view));

        // Test templateRenderParameters.
        $parameters = $this->container->get('united.cms.view_type_manager')->getTemplateRenderParameters($view);
        $this->assertTrue($parameters->isSelectModeNone());
        $this->assertEquals([
            'title' => 'Title',
            'foo' => 'baa',
        ],$parameters->get('columns'));
        $this->assertEquals('position', $parameters->get('sort_field'));
    }
}