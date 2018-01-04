<?php

namespace UnitedCMS\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\Type;

use Symfony\Component\Validator\Constraints as Assert;
use UnitedCMS\CoreBundle\Validator\Constraints\DefaultCollectionType;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidCollectionContentType;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidContentTranslationOf;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidContentTranslations;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidFieldableContentLocale;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidFieldableContentData;

/**
 * Content
 *
 * @ORM\Table(name="content")
 * @ORM\Entity
 * @Gedmo\Loggable
 * @Gedmo\SoftDeleteable(fieldName="deleted", timeAware=false)
 */
class Content implements FieldableContent
{
    /**
     * @var int
     *
     * @ORM\Column(type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;

    /**
     * @var ContentType
     * @Assert\NotBlank(message="validation.not_blank")
     * @ORM\ManyToOne(targetEntity="UnitedCMS\CoreBundle\Entity\ContentType", inversedBy="content", fetch="EXTRA_LAZY")
     */
    protected $contentType;

    /**
     * @var string
     * @Assert\Locale()
     * @ValidFieldableContentLocale(message="validation.invalid_locale")
     * @ORM\Column(type="string", nullable=true)
     */
    protected $locale;

    /**
     * @var array
     * @ValidFieldableContentData(additionalDataMessage="validation.additional_data")
     * @Gedmo\Versioned
     * @ORM\Column(name="data", type="json", nullable=true)
     */
    protected $data = [];

    /**
     * @var Content[]
     * @Type("ArrayCollection<UnitedCMS\CoreBundle\Entity\Content>")
     * @Accessor(getter="geTranslations",setter="setTranslations")
     * @ValidContentTranslations(uniqueLocaleMessage="validation.unique_translations", nestedTranslationMessage="validation.nested_translations")
     * @ORM\OneToMany(targetEntity="UnitedCMS\CoreBundle\Entity\Content", mappedBy="translationOf", fetch="EXTRA_LAZY")
     */
    private $translations;

    /**
     * @var Content
     * @Type("UnitedCMS\CoreBundle\Entity\Content")
     * @Accessor(getter="geTranslationOf",setter="setTranslationOf")
     * @ORM\ManyToOne(targetEntity="Content", inversedBy="translations")
     * @ValidContentTranslationOf(uniqueLocaleMessage="validation.unique_translations")
     * @ORM\JoinColumn(name="translation_of_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $translationOf;

    /**
     * @var ContentInCollection[]
     * @Type("ArrayCollection<UnitedCMS\CoreBundle\Entity\ContentInCollection>")
     * @Accessor(getter="getCollections",setter="setCollections")
     * @DefaultCollectionType(message="validation.missing_default_collection")
     * @ValidCollectionContentType(message="validation.invalid_collection_content_type")
     * @ORM\OneToMany(targetEntity="UnitedCMS\CoreBundle\Entity\ContentInCollection", mappedBy="content", cascade={"persist", "remove", "merge"}, fetch="EXTRA_LAZY")
     */
    private $collections;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    private $updated;

    /**
     * @var \DateTime $deleted
     *
     * @ORM\Column(name="deleted", type="datetime", nullable=true)
     */
    private $deleted;

    public function __construct()
    {
        $this->collections = new ArrayCollection();
        $this->translations = new ArrayCollection();
        $this->translationOf = null;
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ContentInCollection[]|ArrayCollection
     */
    public function getCollections()
    {
        return $this->collections;
    }

    /**
     * @param ContentInCollection[] $collections
     *
     * @return Content
     */
    public function setCollections($collections)
    {
        $this->collections->clear();
        foreach ($collections as $collection) {
            $this->addCollection($collection);
        }

        return $this;
    }

    /**
     * @param ContentInCollection $collection
     *
     * @return Content
     */
    public function addCollection(ContentInCollection $collection)
    {
        if (!$this->collections->containsKey($collection->getCollection()->getIdentifier())) {
            $this->collections->set($collection->getCollection()->getIdentifier(), $collection);
            $collection->setContent($this);
        }

        return $this;
    }

    /**
     * @param Fieldable $entity
     *
     * @return Content
     */
    public function setEntity(Fieldable $entity)
    {
        if ($entity instanceof ContentType) {
            $this->setContentType($entity);
        }

        return $this;
    }

    /**
     * @return Fieldable
     */
    public function getEntity()
    {
        return $this->getContentType();
    }

    /**
     * @param ContentType $contentType
     *
     * @return Content
     */
    public function setContentType(ContentType $contentType)
    {
        $this->contentType = $contentType;

        // Content must always be in the all collection. Additionally it can be in 0..n other collections.
        if (!$this->collections->containsKey('all')) {
            $allCollectionBridge = new ContentInCollection();
            $allCollectionBridge->setCollection($contentType->getCollection('all'));
            $this->addCollection($allCollectionBridge);
        }

        return $this;
    }

    /**
     * @return ContentType
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param string $locale
     *
     * @return Content
     */
    public function setLocale(string $locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Set data
     *
     * @param array $data
     *
     * @return Content
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData() : array
    {
        return $this->data;
    }

    /**
     * Returns all translations for this element including itself.
     * @return ArrayCollection|Content[]
     */
    public function getAllTranslations()
    {
        if(!empty($this->getTranslationOf())) {
            $translations = new ArrayCollection($this->getTranslationOf()->getTranslations()->toArray());
            $translations->add($this->getTranslationOf());
            return $translations;
        }

        $translations = new ArrayCollection($this->getTranslations()->toArray());
        $translations->add($this);
        return $translations;
    }

    /**
     * @return Content[]|ArrayCollection
     */
    public function getTranslations()
    {
        return $this->translations;
    }

    /**
     * @param ArrayCollection|Content[] $translations
     * @return Content
     */
    public function setTranslations($translations)
    {
        $this->translations = $translations;
        return $this;
    }

    public function addTranslation(Content $translation) {

        // Check if content is not already a translation.
        if(!$translation->getTranslationOf()) {

            // Check that locale is supported.
            if(in_array($translation->getLocale(), $this->getContentType()->getLocales())) {
                if(!$this->translations->contains($translation)) {
                    $this->translations->add($translation);
                    $translation->setTranslationOf($this);
                }
            }
        }

        return $this;
    }

    /**
     * @return Content
     */
    public function getTranslationOf()
    {
        return $this->translationOf;
    }

    /**
     * @param Content|null $translationOf
     * @return Content
     */
    public function setTranslationOf($translationOf)
    {
        if($translationOf && $translationOf->getTranslationOf() != null) {
            $this->translationOf = $translationOf->getTranslationOf();
        } else {
            $this->translationOf = $translationOf;
        }

        // If this translation is not already part of it's owners translations, add it.
        $this->translationOf->addTranslation($this);

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * @return \DateTime
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * @return Content
     */
    public function recoverDeleted() {
        $this->deleted = null;

        return $this;
    }
}

