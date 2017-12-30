<?php

namespace UnitedCMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * ContentInCollection
 *
 * @ORM\Table(name="content_in_collection")
 * @ORM\Entity()
 */
class ContentInCollection
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \stdClass
     *
     * @ORM\Column(name="settings", type="object", nullable=true)
     */
    private $settings;

    /**
     * @var Collection
     * @Assert\NotBlank()
     * @ORM\ManyToOne(targetEntity="UnitedCMS\CoreBundle\Entity\Collection", inversedBy="content")
     */
    private $collection;

    /**
     * @var Content
     * @Assert\NotBlank()
     * @ORM\ManyToOne(targetEntity="UnitedCMS\CoreBundle\Entity\Content", inversedBy="collections")
     */
    private $content;


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
     * Set settings
     *
     * @param \stdClass $settings
     *
     * @return ContentInCollection
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * Get settings
     *
     * @return \stdClass
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @return Collection
     */
    public function getCollection(): Collection
    {
        return $this->collection;
    }

    /**
     * @param Collection $collection
     *
     * @return ContentInCollection
     */
    public function setCollection(Collection $collection)
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * @return Content
     */
    public function getContent(): Content
    {
        return $this->content;
    }

    /**
     * @param Content $content
     *
     * @return ContentInCollection
     */
    public function setContent(Content $content)
    {
        $this->content = $content;

        return $this;
    }
}

