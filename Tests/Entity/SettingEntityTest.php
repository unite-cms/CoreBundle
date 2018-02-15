<?php

namespace UnitedCMS\CoreBundle\Tests\Entity;

use Symfony\Component\Validator\ConstraintViolation;
use UnitedCMS\CoreBundle\Entity\FieldableField;
use UnitedCMS\CoreBundle\Entity\Setting;
use UnitedCMS\CoreBundle\Entity\SettingType;
use UnitedCMS\CoreBundle\Entity\SettingTypeField;
use UnitedCMS\CoreBundle\Field\FieldType;
use UnitedCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class SettingEntityTest extends DatabaseAwareTestCase
{

    public function testValidateSetting()
    {

        // Try to validate empty Setting.
        $setting = new Setting();
        $errors = $this->container->get('validator')->validate($setting);
        $this->assertCount(1, $errors);

        $this->assertEquals('settingType', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.not_blank', $errors->get(0)->getMessage());
    }

    public function testValidateAdditionalContentData()
    {
        // 1. Create Setting Type with 1 Field
        $st = new SettingType();
        $field = new SettingTypeField();
        $field->setType('text')->setIdentifier('title')->setTitle('Title');
        $st->setTitle('St1')->setIdentifier('st1')->addField($field);

        // 2. Create Setting1 with the same field. => VALID
        $setting = new Setting();
        $setting->setSettingType($st)->setData(['title' => 'Title']);
        $this->assertCount(0, $this->container->get('validator')->validate($setting));

        // 3. Create Setting2 with the same field and another field. => INVALID
        $setting->setData(array_merge($setting->getData(), ['other' => "Other"]));
        $errors = $this->container->get('validator')->validate($setting);
        $this->assertCount(1, $errors);
        $this->assertEquals('data', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.additional_data', $errors->get(0)->getMessage());

        // 4. Create Setting2 with only another field. => INVALID
        $setting->setData(['other' => 'Other']);
        $errors = $this->container->get('validator')->validate($setting);
        $this->assertCount(1, $errors);
        $this->assertEquals('data', $errors->get(0)->getPropertyPath());
        $this->assertEquals('validation.additional_data', $errors->get(0)->getMessage());

        // 5. SettingType have more fields than setting. => VALID
        $field2 = new SettingTypeField();
        $field2->setType('text')->setIdentifier('title2')->setTitle('Title2');
        $st->addField($field);
        $setting->setSettingType($st)->setData(['title' => 'Title']);
        $this->assertCount(0, $this->container->get('validator')->validate($setting));
    }

    public function testValidateContentDataValidation()
    {

        // 1. Create Content Type with 1 mocked FieldType
        $mockedFieldType = new Class extends FieldType
        {
            const TYPE = "setting_entity_test_mocked_field";

            function validateData(FieldableField $field, $data): array
            {
                if ($data) {
                    return [
                        new ConstraintViolation(
                            'mocked_message',
                            'mocked_message',
                            [],
                            $data,
                            'invalid',
                            $data
                        ),
                    ];
                }

                return [];
            }
        };

        // Inject the field type
        $this->container->get('united.cms.field_type_manager')->registerFieldType($mockedFieldType);

        $st = new SettingType();
        $field = new SettingTypeField();
        $field->setType('setting_entity_test_mocked_field')->setIdentifier('invalid')->setTitle('Title');
        $st->setTitle('St1')->setIdentifier('st1')->addField($field);


        // 2. Create Setting that is invalid with FieldType. => INVALID (at path)
        $setting = new Setting();
        $setting->setSettingType($st)->setData(['invalid' => true]);
        $errors = $this->container->get('validator')->validate($setting);
        $this->assertCount(1, $errors);
        $this->assertEquals('data.invalid', $errors->get(0)->getPropertyPath());
        $this->assertEquals('mocked_message', $errors->get(0)->getMessage());

        // 3. Create Setting that is valid with FieldType. => VALID
        $setting->setData(['invalid' => false]);
        $this->assertCount(0, $this->container->get('validator')->validate($setting));
    }
}