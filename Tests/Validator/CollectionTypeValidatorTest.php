<?php

namespace UnitedCMS\CoreBundle\Tests\Validator;

use UnitedCMS\CoreBundle\Collection\CollectionTypeManager;
use UnitedCMS\CoreBundle\Tests\ConstraintValidatorTestCase;
use UnitedCMS\CoreBundle\Validator\Constraints\CollectionType;
use UnitedCMS\CoreBundle\Validator\Constraints\CollectionTypeValidator;

class CollectionTypeValidatorTest extends ConstraintValidatorTestCase
{
    protected $constraintClass = CollectionType::class;

    public function testInvalidValue() {

        // Create validator with mocked collectionTypeManager.
        $collectionTypeManagerMock = $this->createMock(CollectionTypeManager::class);

        // Validate value.
        $context = $this->validate('any_wrong_value', new CollectionTypeValidator($collectionTypeManagerMock));
        $this->assertCount(1, $context->getViolations());
        $this->assertEquals('This type is not a registered collection type.', $context->getViolations()->get(0)->getMessageTemplate());
    }

    public function testValidValue() {

        // Create validator with mocked collectionTypeManager.
        $collectionTypeManagerMock = $this->createMock(CollectionTypeManager::class);
        $collectionTypeManagerMock->expects($this->any())
            ->method('hasCollectionType')
            ->willReturn(true);

        // Validate value.
        $context = $this->validate('any_wrong_value', new CollectionTypeValidator($collectionTypeManagerMock));
        $this->assertCount(0, $context->getViolations());
    }

    public function testNonStringValue() {

        // Create validator with mocked collectionTypeManager.
        $collectionTypeManagerMock = $this->createMock(CollectionTypeManager::class);
        $collectionTypeManagerMock->expects($this->any())
            ->method('hasCollectionType')
            ->willReturn(true);

        // Validate value.
        $context = $this->validate(1, new CollectionTypeValidator($collectionTypeManagerMock));
        $this->assertCount(1, $context->getViolations());
        $this->assertEquals('This type is not a registered collection type.', $context->getViolations()->get(0)->getMessageTemplate());
    }
}