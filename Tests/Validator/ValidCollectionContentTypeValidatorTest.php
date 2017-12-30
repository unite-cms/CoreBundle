<?php

namespace UnitedCMS\CoreBundle\Tests\Validator;

use Symfony\Component\Validator\Exception\InvalidArgumentException;
use UnitedCMS\CoreBundle\Entity\Collection;
use UnitedCMS\CoreBundle\Entity\Content;
use UnitedCMS\CoreBundle\Entity\ContentInCollection;
use UnitedCMS\CoreBundle\Entity\ContentType;
use UnitedCMS\CoreBundle\Tests\ConstraintValidatorTestCase;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidCollectionContentType;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidCollectionContentTypeValidator;

class ValidCollectionContentTypeValidatorTest extends ConstraintValidatorTestCase
{
    protected $constraintClass = ValidCollectionContentType::class;
    protected $constraintValidatorClass = ValidCollectionContentTypeValidator::class;

    /**
     * @expectedException InvalidArgumentException
     */
    public function testNonContentValue() {
        $this->validate();
    }

    public function testInvalidValue() {
        $ct1 = new ContentType();
        $ct2 = new ContentType();
        $ct1->setIdentifier('ct1');
        $ct2->setIdentifier('ct2');
        $content = new Content();
        $content->setContentType($ct1);
        $collection = new Collection();
        $collection->setContentType($ct2);
        $cIc = new ContentInCollection();
        $cIc->setCollection($collection)->setContent($content);
        $content->addCollection($cIc);
        $collection->addContent($cIc);

        $context = $this->validate($content->getCollections(), null, null, $content);
        $this->assertCount(1, $context->getViolations());
        $this->assertEquals('Content and its collections must be in the same contentType', $context->getViolations()->get(0)->getMessageTemplate());
    }

    public function testValidValue() {
        $ct1 = new ContentType();
        $ct1->setIdentifier('ct1');
        $content = new Content();
        $content->setContentType($ct1);
        $collection = new Collection();
        $collection->setContentType($ct1);
        $cIc = new ContentInCollection();
        $cIc->setCollection($collection)->setContent($content);
        $content->addCollection($cIc);
        $collection->addContent($cIc);

        $context = $this->validate($content->getCollections(), null, null, $content);
        $this->assertCount(0, $context->getViolations());
    }
}