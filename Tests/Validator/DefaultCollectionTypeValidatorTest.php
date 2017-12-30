<?php

namespace UnitedCMS\CoreBundle\Tests\Validator;

use UnitedCMS\CoreBundle\Entity\Collection;
use UnitedCMS\CoreBundle\Entity\ContentInCollection;
use UnitedCMS\CoreBundle\Tests\ConstraintValidatorTestCase;
use UnitedCMS\CoreBundle\Validator\Constraints\DefaultCollectionType;
use UnitedCMS\CoreBundle\Validator\Constraints\DefaultCollectionTypeValidator;

class DefaultCollectionTypeValidatorTest extends ConstraintValidatorTestCase
{
    protected $constraintClass = DefaultCollectionType::class;
    protected $constraintValidatorClass = DefaultCollectionTypeValidator::class;

    public function testInvalidValue() {
        $context = $this->validate([]);
        $this->assertCount(1, $context->getViolations());
        $this->assertEquals('The default collection type is missing', $context->getViolations()->get(0)->getMessageTemplate());
    }

    public function testValidContentInCollectionValue() {
        $c = new ContentInCollection();
        $c->setCollection(new Collection())->getCollection()->setIdentifier(Collection::DEFAULT_COLLECTION_IDENTIFIER);
        $context = $this->validate([$c]);
        $this->assertCount(0, $context->getViolations());
    }

    public function testValidCollectionValue() {
        $c = new Collection();
        $c->setIdentifier(Collection::DEFAULT_COLLECTION_IDENTIFIER);
        $context = $this->validate([$c]);
        $this->assertCount(0, $context->getViolations());
    }

    public function testInValidContentInCollectionValue() {
        $c = new ContentInCollection();
        $c->setCollection(new Collection())->getCollection()->setIdentifier('any_other');
        $context = $this->validate([$c]);
        $this->assertCount(1, $context->getViolations());
        $this->assertEquals('The default collection type is missing', $context->getViolations()->get(0)->getMessageTemplate());
    }

    public function testInValidCollectionValue() {
        $c = new Collection();
        $c->setIdentifier('any_other');
        $context = $this->validate([$c]);
        $this->assertCount(1, $context->getViolations());
        $this->assertEquals('The default collection type is missing', $context->getViolations()->get(0)->getMessageTemplate());
    }
}