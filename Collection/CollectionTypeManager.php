<?php

namespace UnitedCMS\CoreBundle\Collection;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\ConstraintViolation;
use UnitedCMS\CoreBundle\Entity\Collection;

class CollectionTypeManager
{
    /**
     * @var CollectionTypeInterface[]
     */
    private $collectionTypes = [];

    /**
     * @var UrlGeneratorInterface $urlGenerator
     */
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @return CollectionTypeInterface[]
     */
    public function getCollectionTypes(): array
    {
        return $this->collectionTypes;
    }

    public function hasCollectionType($key): bool
    {
        return array_key_exists($key, $this->collectionTypes);
    }

    public function getCollectionType($key): CollectionTypeInterface
    {
        if (!$this->hasCollectionType($key)) {
            throw new \InvalidArgumentException("The collection type: '$key' was not found.");
        }

        return $this->collectionTypes[$key];
    }

    /**
     * Get template render parameters for the given collection.
     * @param Collection $collection
     * @param string $select_mode
     *
     * @return CollectionParameterBag
     */
    public function getTemplateRenderParameters(Collection $collection, $select_mode = CollectionTypeInterface::SELECT_MODE_NONE): CollectionParameterBag
    {
        $collectionType = $this->getCollectionType($collection->getType());
        $collectionType->setCollection($collection);
        $settings = $collectionType->getTemplateRenderParameters($select_mode);
        $collectionType->unsetCollection();

        return CollectionParameterBag::createFromCollection($collection, $this->urlGenerator, $select_mode, $settings ?? []);
    }

    /**
     * Validates collection settings for given collection by using the validation method of the collection type.
     * @param Collection $collection
     * @param CollectionSettings $settings
     *
     * @return ConstraintViolation[]
     */
    public function validateCollectionSettings(Collection $collection, CollectionSettings $settings): array
    {
        $collectionType = $this->getCollectionType($collection->getType());
        $collectionType->setCollection($collection);
        $constraints = $collectionType->validateSettings($settings);
        $collectionType->unsetCollection();

        return $constraints;
    }

    /**
     * @param CollectionTypeInterface $collectionType
     *
     * @return CollectionTypeManager
     */
    public function registerCollectionType(CollectionTypeInterface $collectionType)
    {
        if (!isset($this->collectionTypes[$collectionType::getType()])) {
            $this->collectionTypes[$collectionType::getType()] = $collectionType;
        }

        return $this;
    }
}