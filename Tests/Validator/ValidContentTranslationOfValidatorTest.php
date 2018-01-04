<?php

namespace UnitedCMS\CoreBundle\Tests\Validator;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use UnitedCMS\CoreBundle\Entity\Content;
use UnitedCMS\CoreBundle\Entity\ContentType;
use UnitedCMS\CoreBundle\Entity\ContentTypeField;
use UnitedCMS\CoreBundle\Entity\Fieldable;
use UnitedCMS\CoreBundle\Entity\FieldableContent;
use UnitedCMS\CoreBundle\Field\FieldTypeManager;
use UnitedCMS\CoreBundle\Tests\ConstraintValidatorTestCase;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidContentTranslationOf;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidContentTranslationOfValidator;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidContentTranslations;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidContentTranslationsValidator;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidFieldableContentDataValidator;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidFieldableContentLocale;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidFieldableContentLocaleValidator;

class ValidContentTranslationOfValidatorTest extends ConstraintValidatorTestCase
{
    protected $constraintClass = ValidContentTranslationOf::class;

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The ValidContentTranslationOfValidator constraint expects a UnitedCMS\CoreBundle\Entity\Content object.
     */
    public function testInvalidObject() {
        $object = new \stdClass();
        $this->validate((object)[], new ValidContentTranslationOfValidator(), null, $object);
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The ValidContentTranslationOfValidator constraint expects a UnitedCMS\CoreBundle\Entity\Content value.
     */
    public function testInvalidValue() {
        $object = new Content();
        $this->validate((object)[], new ValidContentTranslationOfValidator(), null, $object);
    }

    public function testEmptyObjectAndContextObject() {
        $object = new Content();

        // When validating an empty value or don't provide a context object, the validator just skips this.
        $context = $this->validate(null, new ValidContentTranslationOfValidator(), null, $object);
        $this->assertCount(0, $context->getViolations());

        $context = $this->validate(new Content(), new ValidContentTranslationOfValidator());
        $this->assertCount(0, $context->getViolations());
    }

    public function testDuplicatedLocale() {
        $object = new Content();
        $object->setLocale('de');
        $value = new Content();
        $value->setLocale('de');

        $errors = $this->validate($value, new ValidContentTranslationOfValidator(), null, $object);
        $this->assertCount(1, $errors->getViolations());
        $this->assertEquals('There are two ore more translations in the same language.', $errors->getViolations()->get(0)->getMessageTemplate());

        $object->setLocale('en');
        $errors = $this->validate($value, new ValidContentTranslationOfValidator(), null, $object);
        $this->assertCount(0, $errors->getViolations());
    }
}