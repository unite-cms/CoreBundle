<?php

namespace UnitedCMS\CoreBundle\Tests\Entity;

use UnitedCMS\CoreBundle\Entity\Collection;
use UnitedCMS\CoreBundle\Entity\Content;
use UnitedCMS\CoreBundle\Entity\ContentInCollection;
use UnitedCMS\CoreBundle\Entity\ContentType;
use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\Entity\Organization;
use UnitedCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class CollectionEntityTest extends DatabaseAwareTestCase
{

    public function testValidateCollection()
    {

        // Try to validate empty Collection.
        $collection = new Collection();
        $collection->setIdentifier('')->setTitle('')->setContent([]);
        $errors = $this->container->get('validator')->validate($collection);
        $this->assertCount(5, $errors);

        $this->assertEquals('title', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(0)->getMessage());

        $this->assertEquals('identifier', $errors->get(1)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(1)->getMessage());

        $this->assertEquals('type', $errors->get(2)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(2)->getMessage());

        $this->assertEquals('type', $errors->get(3)->getPropertyPath());
        $this->assertEquals('validation.invalid_collection_type', $errors->get(3)->getMessage());

        $this->assertEquals('contentType', $errors->get(4)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(4)->getMessage());

        // Try to validate too long title, identifier, type
        $collection
            ->setTitle($this->generateRandomUTF8String(256))
            ->setIdentifier($this->generateRandomMachineName(256))
            ->setType($this->generateRandomMachineName(256))
            ->setContentType(new ContentType())
            ->getContentType()
            ->setIdentifier('ct')->setTitle('ct')->setDomain(new Domain())
            ->getDomain()->setTitle('domain')->setIdentifier('domain')->setOrganization(new Organization())
            ->getOrganization()->setIdentifier('org')->setTitle('org');

        $errors = $this->container->get('validator')->validate($collection);
        $this->assertCount(4, $errors);

        $this->assertEquals('title', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.too_long', $errors->get(0)->getMessage());

        $this->assertEquals('identifier', $errors->get(1)->getPropertyPath());
        $this->assertEquals('validation.too_long', $errors->get(1)->getMessage());

        $this->assertEquals('type', $errors->get(2)->getPropertyPath());
        $this->assertEquals('validation.too_long', $errors->get(2)->getMessage());

        $this->assertEquals('type', $errors->get(3)->getPropertyPath());
        $this->assertEquals('validation.invalid_collection_type', $errors->get(3)->getMessage());

        // Try to validate invalid type
        $collection
            ->setTitle($this->generateRandomUTF8String(255))
            ->setIdentifier('identifier')
            ->setType('invalid');
        $errors = $this->container->get('validator')->validate($collection);
        $this->assertCount(1, $errors);
        $this->assertEquals('type', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.invalid_collection_type', $errors->get(0)->getMessage());

        // Try to validate invalid identifier
        $collection
            ->setIdentifier('#')
            ->setType('table');

        $errors = $this->container->get('validator')->validate($collection);
        $this->assertCount(1, $errors);

        $this->assertEquals('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.invalid_characters', $errors->get(0)->getMessage());

        // Try to validate invalid icon
        $collection
            ->setIdentifier('collection')
            ->setIcon($this->generateRandomMachineName(256));
        $errors = $this->container->get('validator')->validate($collection);
        $this->assertCount(1, $errors);
        $this->assertEquals('icon', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.too_long', $errors->get(0)->getMessage());

        $collection->setIcon('# ');
        $errors = $this->container->get('validator')->validate($collection);
        $this->assertCount(1, $errors);
        $this->assertEquals('icon', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.invalid_characters', $errors->get(0)->getMessage());

        // Try to validate invalid ContentInCollection
        $collection->setIcon(null)->addContent(new ContentInCollection());
        $errors = $this->container->get('validator')->validate($collection);
        $this->assertCount(1, $errors);
        $this->assertStringStartsWith('content', $errors->get(0)->getPropertyPath());

        // Test UniqueEntity Validation.
        $collection->getContent()->clear();
        $this->em->persist($collection->getContentType()->getDomain()->getOrganization());
        $this->em->persist($collection->getContentType()->getDomain());
        $this->em->persist($collection);
        $this->em->flush($collection);
        $this->em->refresh($collection);

        $collection2 = new Collection();
        $collection2
            ->setTitle($collection->getTitle())
            ->setIdentifier($collection->getIdentifier())
            ->setContentType($collection->getContentType())
            ->setType('table');
        $errors = $this->container->get('validator')->validate($collection2);
        $this->assertCount(1, $errors);

        $this->assertEquals('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.identifier_already_taken', $errors->get(0)->getMessage());
    }

    public function testDeletingCollectionWillNotDeleteContent()
    {
        $collection = new Collection();
        $collection
            ->setTitle($this->generateRandomUTF8String(10))
            ->setIdentifier($this->generateRandomMachineName(10))
            ->setType('table')
            ->setContentType(new ContentType())
            ->getContentType()
            ->setIdentifier('ct')->setTitle('ct')->setDomain(new Domain())
            ->getDomain()->setTitle('domain')->setIdentifier('domain')->setOrganization(new Organization())
            ->getOrganization()->setIdentifier('org')->setTitle('org');

        $this->assertCount(0, $this->container->get('validator')->validate($collection));
        $this->em->persist($collection->getContentType()->getDomain()->getOrganization());
        $this->em->persist($collection->getContentType()->getDomain());
        $this->em->persist($collection);
        $this->em->flush($collection);
        $this->em->refresh($collection);

        $content = new Content();
        $cic = new ContentInCollection();
        $cic->setCollection($collection);
        $content->addCollection($cic)->setContentType($collection->getContentType());
        $this->assertCount(0, $this->container->get('validator')->validate($content));
        $this->em->persist($content);
        $this->em->flush($content);
        $this->em->refresh($content);

        $this->em->remove($collection);
        $this->em->flush();
        $this->em->refresh($content);
        $this->em->refresh($content->getContentType());
        $this->em->refresh($content->getContentType()->getCollection('all'));

        $this->assertNotNull($content->getId());
        $this->assertCount(1, $content->getContentType()->getCollection('all')->getContent());
    }

    public function testReservedIdentifiers()
    {
        $reserved = Collection::RESERVED_IDENTIFIERS;
        $this->assertNotEmpty($reserved);

        $collection = new Collection();
        $collection
            ->setTitle($this->generateRandomUTF8String(255))
            ->setIdentifier(array_pop($reserved))
            ->setType('table')
            ->setContentType(new ContentType())
            ->getContentType()
            ->setIdentifier('ct')->setTitle('ct')->setDomain(new Domain())
            ->getDomain()->setTitle('domain')->setIdentifier('domain')->setOrganization(new Organization())
            ->getOrganization()->setIdentifier('org')->setTitle('org');

        $errors = $this->container->get('validator')->validate($collection);
        $this->assertCount(1, $errors);
        $this->assertStringStartsWith('identifier', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.reserved_identifier', $errors->get(0)->getMessage());
    }
}