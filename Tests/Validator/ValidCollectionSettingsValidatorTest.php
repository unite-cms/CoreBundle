<?php

namespace UnitedCMS\CoreBundle\Tests\Validator;

use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use UnitedCMS\CoreBundle\Collection\CollectionSettings;
use UnitedCMS\CoreBundle\Collection\CollectionType;
use UnitedCMS\CoreBundle\Collection\CollectionTypeManager;
use UnitedCMS\CoreBundle\Entity\Collection;
use UnitedCMS\CoreBundle\Entity\ContentTypeField;
use UnitedCMS\CoreBundle\Entity\SettingTypeField;
use UnitedCMS\CoreBundle\Field\FieldableFieldSettings;
use UnitedCMS\CoreBundle\Field\FieldTypeManager;
use UnitedCMS\CoreBundle\Tests\ConstraintValidatorTestCase;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidCollectionSettings;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidCollectionSettingsValidator;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidFieldSettings;
use UnitedCMS\CoreBundle\Validator\Constraints\ValidFieldSettingsValidator;

class ValidCollectionSettingsValidatorTest extends ConstraintValidatorTestCase
{
    protected $constraintClass = ValidCollectionSettings::class;

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The ValidCollectionSettingsValidator constraint expects a UnitedCMS\CoreBundle\Collection\CollectionSettings value.
     */
    public function testNonContentValue() {
        // Create validator with mocked CollectionTypeManager.
        $collectionTypeManagerMock = $this->createMock(CollectionTypeManager::class);

        // Validate value.
        $this->validate((object)[], new ValidCollectionSettingsValidator($collectionTypeManagerMock));
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The ValidCollectionSettingsValidator constraint expects a UnitedCMS\CoreBundle\Entity\Collection object.
     */
    public function testNonContextObject() {
        // Create validator with mocked CollectionTypeManager.
        $collectionTypeManagerMock = $this->createMock(CollectionTypeManager::class);

        // Validate value.
        $this->validate(new CollectionSettings(), new ValidCollectionSettingsValidator($collectionTypeManagerMock));
    }

    public function testInvalidValue() {
        // Create validator with mocked CollectionTypeManager.
        $collectionTypeManagerMock = $this->createMock(CollectionTypeManager::class);
        $collectionTypeManagerMock->expects($this->any())
            ->method('validateCollectionSettings')
            ->willReturn([
                new ConstraintViolation('m1', 'm1', [], 'root', 'root', 'i1'),
                new ConstraintViolation('m2', 'm2', [], 'root', 'root', 'i2'),
            ]);
        $collectionTypeManagerMock->expects($this->any())
            ->method('hasCollectionType')
            ->willReturn(true);

        // Validate value.
        $context = $this->validate(new CollectionSettings(), new ValidCollectionSettingsValidator($collectionTypeManagerMock), null, new Collection());
        $this->assertCount(2, $context->getViolations());
        $this->assertEquals('m1', $context->getViolations()->get(0)->getMessageTemplate());
        $this->assertEquals('m2', $context->getViolations()->get(1)->getMessageTemplate());
    }

    public function testValidValue() {
        // Create validator with mocked CollectionTypeManager.
        $collectionTypeManagerMock = $this->createMock(CollectionTypeManager::class);
        $collectionTypeManagerMock->expects($this->any())
            ->method('validateCollectionSettings')
            ->willReturn([]);

        $collectionTypeManagerMock->expects($this->any())
            ->method('hasCollectionType')
            ->willReturn(true);

        // Validate value.
        $context = $this->validate(new CollectionSettings(), new ValidCollectionSettingsValidator($collectionTypeManagerMock), null, new Collection());
        $this->assertCount(0, $context->getViolations());
    }
}