<?php

namespace UnitedCMS\CoreBundle\Tests\View;

use UnitedCMS\CoreBundle\View\ViewSettings;
use UnitedCMS\CoreBundle\Entity\View;
use UnitedCMS\CoreBundle\Entity\ContentType;
use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\Entity\Organization;
use UnitedCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class TableViewTypeTest extends DatabaseAwareTestCase
{

    public function testTableViewWithoutSettings() {

        // Create TableView instance.
        $view = new View();
        $view
            ->setType('table')
            ->setTitle('New View')
            ->setIdentifier('new_view')
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
        $this->assertEquals([
            'field' => 'updated',
            'asc' => false,
        ],$parameters->get('sort'));
    }

    public function testTableViewWithInvalidSettings() {

        // Create TableView instance.
        $view = new View();
        $view
            ->setType('table')
            ->setTitle('New View')
            ->setIdentifier('new_view')
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

        $view->setSettings(new ViewSettings([
            'foo' => 'baa',
        ]));

        // View should not be valid.
        $errors = $this->container->get('validator')->validate($view);
        $this->assertCount(1, $errors);
        $this->assertEquals('validation.additional_data', $errors->get(0)->getMessage());
    }

    public function testTableViewWithValidSettings() {

        // Create TableView instance.
        $view = new View();
        $view
            ->setType('table')
            ->setTitle('New View')
            ->setIdentifier('new_view')
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

        $view->setSettings(new ViewSettings([
            'columns' => [
                'title' => 'Title',
                'foo' => 'baa',
            ],
            'sort_field' => 'foo',
            'sort_asc' => true,
        ]));

        // View should be valid.
        $this->assertCount(0, $this->container->get('validator')->validate($view));

        // Test templateRenderParameters.
        $parameters = $this->container->get('united.cms.view_type_manager')->getTemplateRenderParameters($view);
        $this->assertTrue($parameters->isSelectModeNone());
        $this->assertEquals([
            'title' => 'Title',
            'foo' => 'baa',
        ],$parameters->get('columns'));
        $this->assertEquals([
            'field' => 'foo',
            'asc' => true,
        ],$parameters->get('sort'));
    }
}