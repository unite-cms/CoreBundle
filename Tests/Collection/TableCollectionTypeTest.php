<?php

namespace UnitedCMS\CoreBundle\Tests\Collection;

use UnitedCMS\CoreBundle\Collection\CollectionSettings;
use UnitedCMS\CoreBundle\Entity\Collection;
use UnitedCMS\CoreBundle\Entity\ContentType;
use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\Entity\Organization;
use UnitedCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class TableCollectionTypeTest extends DatabaseAwareTestCase
{

    public function testTableCollectionWithoutSettings() {

        // Create TableCollection instance.
        $collection = new Collection();
        $collection
            ->setType('table')
            ->setTitle('New Collection')
            ->setIdentifier('new_collection')
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

        // Collection should be valid.
        $this->assertCount(0, $this->container->get('validator')->validate($collection));

        // Test templateRenderParameters.
        $parameters = $this->container->get('united.cms.collection_type_manager')->getTemplateRenderParameters($collection);
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

    public function testTableCollectionWithInvalidSettings() {

        // Create TableCollection instance.
        $collection = new Collection();
        $collection
            ->setType('table')
            ->setTitle('New Collection')
            ->setIdentifier('new_collection')
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

        $collection->setSettings(new CollectionSettings([
            'foo' => 'baa',
        ]));

        // Collection should not be valid.
        $errors = $this->container->get('validator')->validate($collection);
        $this->assertCount(1, $errors);
        $this->assertEquals('validation.additional_data', $errors->get(0)->getMessage());
    }

    public function testTableCollectionWithValidSettings() {

        // Create TableCollection instance.
        $collection = new Collection();
        $collection
            ->setType('table')
            ->setTitle('New Collection')
            ->setIdentifier('new_collection')
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

        $collection->setSettings(new CollectionSettings([
            'columns' => [
                'title' => 'Title',
                'foo' => 'baa',
            ],
            'sort_field' => 'foo',
            'sort_asc' => true,
        ]));

        // Collection should be valid.
        $this->assertCount(0, $this->container->get('validator')->validate($collection));

        // Test templateRenderParameters.
        $parameters = $this->container->get('united.cms.collection_type_manager')->getTemplateRenderParameters($collection);
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