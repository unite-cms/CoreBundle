<?php

namespace UnitedCMS\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Accessor;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use JMS\Serializer\Annotation\Type;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use UnitedCMS\CoreBundle\Collection\CollectionSettings;
use UnitedCMS\CoreBundle\Validator\Constraints\CollectionType;
use UnitedCMS\CoreBundle\Validator\Constraints\ReservedWords;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidCollectionSettings;

/**
 * Collection
 *
 * @UniqueEntity(fields={"identifier", "contentType"}, message="validation.identifier_already_taken")
 * @ORM\Table(name="collection")
 * @ORM\Entity(repositoryClass="UnitedCMS\CoreBundle\Repository\CollectionRepository")
 * @ExclusionPolicy("all")
 */
class Collection
{
    const DEFAULT_COLLECTION_IDENTIFIER = "all";
    const RESERVED_IDENTIFIERS = ['create', 'view', 'update', 'delete'];

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     * @Assert\NotBlank(message="validation.not_blank")
     * @Assert\Length(max="255", maxMessage="validation.too_long")
     * @ORM\Column(name="title", type="string", length=255)
     * @Expose
     */
    private $title;

    /**
     * @var string
     * @Assert\NotBlank(message="validation.not_blank")
     * @Assert\Length(max="255", maxMessage="validation.too_long")
     * @Assert\Regex(pattern="/^[a-z0-9_]+$/i", message="validation.invalid_characters")
     * @ReservedWords(message="validation.reserved_identifier", reserved="UnitedCMS\CoreBundle\Entity\Collection::RESERVED_IDENTIFIERS")
     * @ORM\Column(name="identifier", type="string", length=255)
     * @Expose
     */
    private $identifier;

    /**
     * @var string
     * @Assert\NotBlank(message="validation.not_blank")
     * @Assert\Length(max="255", maxMessage="validation.too_long")
     * @CollectionType(message="validation.invalid_collection_type")
     * @ORM\Column(name="type", type="string", length=255)
     * @Expose
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     * @Expose
     */
    private $description;

    /**
     * @var string
     * @Assert\Length(max="255", maxMessage="validation.too_long")
     * @Assert\Regex(pattern="/^[a-z0-9_]+$/i", message="validation.invalid_characters")
     * @ORM\Column(name="icon", type="string", length=255, nullable=true)
     * @Expose
     */
    private $icon;

    /**
     * @var ContentType
     * @Assert\NotBlank(message="validation.not_blank")
     * @Assert\Valid()
     * @ORM\ManyToOne(targetEntity="UnitedCMS\CoreBundle\Entity\ContentType", inversedBy="collections", fetch="EXTRA_LAZY")
     */
    private $contentType;

    /**
     * @var CollectionSettings
     *
     * @ORM\Column(name="settings", type="object", nullable=true)
     * @ValidCollectionSettings()
     * @Assert\NotNull(message="validation.not_null")
     * @Type("UnitedCMS\CoreBundle\Collection\CollectionSettings")
     * @Expose
     */
    private $settings;

    /**
     * @var ContentInCollection[]
     *
     * @Type("ArrayCollection<UnitedCMS\CoreBundle\Entity\ContentInCollection>")
     * @Accessor(getter="getContent",setter="setContent")
     * @Assert\Valid()
     * @ORM\OneToMany(targetEntity="UnitedCMS\CoreBundle\Entity\ContentInCollection", mappedBy="collection", cascade={"persist", "remove", "merge"}, fetch="EXTRA_LAZY")
     */
    private $content;

    public function __construct()
    {
        $this->content = new ArrayCollection();
        $this->settings = new CollectionSettings();
    }

    public function __toString()
    {
        return ''.$this->getTitle();
    }

    /**
     * This function sets all structure fields from the given entity.
     *
     * @param Collection $collection
     * @return Collection
     */
    public function setFromEntity(Collection $collection)
    {
        $this
            ->setTitle($collection->getTitle())
            ->setIdentifier($collection->getIdentifier())
            ->setType($collection->getType())
            ->setDescription($collection->getDescription())
            ->setIcon($collection->getIcon())
            ->setSettings($collection->getSettings());

        return $this;
    }

    /**
     * Set id
     *
     * @param $id
     *
     * @return Collection
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * Set title
     *
     * @param string $title
     *
     * @return Collection
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set identifier
     *
     * @param string $identifier
     *
     * @return Collection
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * Get identifier
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return Collection
     */
    public function setType(string $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Collection
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set icon
     *
     * @param string $icon
     *
     * @return Collection
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Get icon
     *
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @return ContentType
     */
    public function getContentType(): ContentType
    {
        return $this->contentType;
    }

    /**
     * @param ContentType $contentType
     *
     * @return Collection
     */
    public function setContentType(ContentType $contentType)
    {
        $this->contentType = $contentType;
        $contentType->addCollection($this);

        return $this;
    }

    /**
     * Set settings
     *
     * @param CollectionSettings $settings
     *
     * @return Collection
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * Get settings
     *
     * @return CollectionSettings
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @return ContentInCollection[]|ArrayCollection
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param ContentInCollection[] $content
     *
     * @return Collection
     */
    public function setContent($content)
    {
        $this->content->clear();
        foreach ($content as $contentItem) {
            $this->addContent($contentItem);
        }

        return $this;
    }

    /**
     * @param ContentInCollection $content
     *
     * @return Collection
     */
    public function addContent(ContentInCollection $content)
    {
        if (!$this->content->contains($content)) {
            $this->content->add($content);
            $content->setCollection($this);
        }

        return $this;
    }
}

