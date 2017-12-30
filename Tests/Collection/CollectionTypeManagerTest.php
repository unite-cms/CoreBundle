<?php

namespace UnitedCMS\CoreBundle\Tests\Collection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use UnitedCMS\CoreBundle\Collection\CollectionType;
use UnitedCMS\CoreBundle\Collection\CollectionTypeInterface;
use UnitedCMS\CoreBundle\Collection\CollectionTypeManager;
use UnitedCMS\CoreBundle\Entity\Collection;
use UnitedCMS\CoreBundle\Entity\ContentType;
use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\Entity\Organization;

class CollectionTypeManagerTest extends TestCase
{

    public function testRegisterCollections() {

        $collection = new class extends CollectionType{
            const TYPE = "test_register_collection_test_type";
            function getTemplateRenderParameters(string $selectMode = self::SELECT_MODE_NONE): array {
                return [
                    'foo' => 'baa',
                ];
            }
        };

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->any())
            ->method('generate')
            ->willReturn('mocked_url');

        $manager = new CollectionTypeManager($urlGenerator);
        $manager->registerCollectionType($collection);


        // Check that the collection was registered.
        $this->assertEquals($collection, $manager->getCollectionType('test_register_collection_test_type'));

        // Check get template render parameter.
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

        $collection->setType('test_register_collection_test_type');

        $parameters = $manager->getTemplateRenderParameters($collection, CollectionTypeInterface::SELECT_MODE_SINGLE);

        $this->assertTrue($parameters->isSelectModeSingle());
        $this->assertEquals('baa', $parameters->get('foo'));


    }
}