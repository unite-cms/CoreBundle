<?php

namespace UnitedCMS\CoreBundle\Tests\Collection;

use PHPUnit\Framework\TestCase;
use UnitedCMS\CoreBundle\Field\FieldType;
use UnitedCMS\CoreBundle\Field\FieldTypeManager;

class FieldTypeManagerTest extends TestCase
{

    public function testRegisterFields() {

        $fieldType = new class extends FieldType {
            const TYPE = "test_register_field_test_type";
            public function getTitle(): string
            {
                return 'custom_prefix_' . parent::getTitle();
            }
        };

        $manager = new FieldTypeManager();
        $manager->registerFieldType($fieldType);


        // Check that the fieldType was registered.
        $this->assertEquals($fieldType, $manager->getFieldType('test_register_field_test_type'));
    }
}