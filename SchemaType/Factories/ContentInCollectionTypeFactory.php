<?php

namespace UnitedCMS\CoreBundle\SchemaType\Factories;

use Doctrine\ORM\EntityManager;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use UnitedCMS\CoreBundle\Collection\CollectionTypeManager;
use UnitedCMS\CoreBundle\Entity\Collection;
use UnitedCMS\CoreBundle\Entity\ContentInCollection;
use UnitedCMS\CoreBundle\Entity\ContentType;
use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\SchemaType\SchemaTypeManager;

class ContentInCollectionTypeFactory implements SchemaTypeFactoryInterface
{
    /**
     * @var CollectionTypeManager $collectionTypeManager
     */
    private $collectionTypeManager;

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var ObjectType $nestedSettingType
     */
    private $nestedSettingType = null;

    public function __construct(CollectionTypeManager $collectionTypeManager, EntityManager $entityManager)
    {
        $this->collectionTypeManager = $collectionTypeManager;
        $this->entityManager = $entityManager;
    }

    /**
     * Returns true, if this factory can create a schema for the given name.
     *
     * @param string $schemaTypeName
     * @return bool
     */
    public function supports(string $schemaTypeName): bool
    {
        $nameParts = preg_split('/(?=[A-Z])/', $schemaTypeName, -1, PREG_SPLIT_NO_EMPTY);

        if(count($nameParts) !== 3) {
            return false;
        }

        if($nameParts[2] !== 'Collection') {
            return false;
        }

        return true;
    }

    /**
     * Returns the new created schema type object for the given name.
     * @param SchemaTypeManager $schemaTypeManager
     * @param int $nestingLevel
     * @param Domain $domain
     * @param string $schemaTypeName
     * @return Type
     */
    public function createSchemaType(SchemaTypeManager $schemaTypeManager, int $nestingLevel, Domain $domain = null, string $schemaTypeName): Type
    {
        if(!$domain) {
            throw new \InvalidArgumentException('UnitedCMS\CoreBundle\SchemaType\Factories\ContentInCollectionTypeFactory::createSchemaType needs an domain as second argument');
        }

        $nameParts = preg_split('/(?=[A-Z])/', $schemaTypeName, -1, PREG_SPLIT_NO_EMPTY);
        $contentTypeIdentifier = strtolower($nameParts[0]);
        $collectionIdentifier = strtolower($nameParts[1]);

        /**
         * @var Collection $collection
         */
        if (!$collection = $domain->getContentTypes()->get($contentTypeIdentifier)->getCollection($collectionIdentifier)) {
            throw new \InvalidArgumentException(
                "No collection with identifier '$collectionIdentifier' for contentType '$contentTypeIdentifier' found for in the given domain."
            );
        }

        /**
         * @var ContentType $contentType
         */
        $contentType = $domain->getContentTypes()->get($contentTypeIdentifier);

        // Load the full contentType if it is not already loaded.
        if(!$this->entityManager->contains($contentType)) {
            $contentType = $this->entityManager->getRepository('UnitedCMSCoreBundle:ContentType')->find($contentType->getId());
        }

        $collection = $contentType->getCollection($collectionIdentifier);

        return new ObjectType(
            [
                'name' => ucfirst($contentTypeIdentifier) . ucfirst($collectionIdentifier) . 'Collection',
                'fields' => array_merge(
                    [
                        'identifier' => Type::id(),
                        'type' => Type::string(),
                        'settings' => $this->generateNestedSettingType($contentTypeIdentifier, $collectionIdentifier, $collection),
                    ]
                ),
                'resolveField' => function ($value, array $args, $context, ResolveInfo $info) use ($collection) {

                    if (!$value instanceof ContentInCollection) {
                        throw new \InvalidArgumentException(
                            'Value must be instance of '.ContentInCollection::class.'.'
                        );
                    }

                    switch ($info->fieldName) {
                        case 'identifier':
                            return $value->getCollection()->getIdentifier();
                        case 'type':
                            return $value->getCollection()->getType();
                        case 'settings':
                            return $value->getSettings();
                        default:
                            return null;
                    }
                },
                'interfaces' => [$schemaTypeManager->getSchemaType('CollectionInterface')],
            ]
        );
    }

    private function generateNestedSettingType(string $contentTypeIdentifier, string $collectionIdentifier, Collection $collection) : ObjectType {
        if($this->nestedSettingType) {
            return $this->nestedSettingType;
        }

        $fields = [];

        // TODO: The $collectionTypeManager should return possible settings for this collection type.

        return new ObjectType([
            'name' => ucfirst($contentTypeIdentifier) . ucfirst($collectionIdentifier) . 'CollectionSettings',
            'fields' => $fields
        ]);
    }
}