<?php declare(strict_types=1);

/**
 * @maintainer Timur Shagiakhmetov <timur.shagiakhmetov@corp.badoo.com>
 */

namespace unit\Badoo\LiveProfilerUI;

class FieldListTest extends \unit\Badoo\BaseTestCase
{
    public function testFields()
    {
        $fields = ['field1', ['field2_profile' => 'field2']];
        $variations = ['min', 'max'];
        $descriptions = [];
        $FieldList = new \Badoo\LiveProfilerUI\FieldList($fields, $variations, $descriptions);

        $expected_fields = [
            'field1' => 'field1',
            'field2_profile' => 'field2'
        ];

        $expected_fields_with_variations = [
            'field1' => 'field1',
            'min_field1' => 'min_field1',
            'max_field1' => 'max_field1',
            'field2' => 'field2',
            'min_field2' => 'min_field2',
            'max_field2' => 'max_field2'
        ];

        self::assertEquals($expected_fields, $FieldList->getFields());
        self::assertEquals($variations, $FieldList->getFieldVariations());
        self::assertEquals($descriptions, $FieldList->getFieldDescriptions());
        self::assertEquals($expected_fields_with_variations, $FieldList->getAllFieldsWithVariations());
    }
}
