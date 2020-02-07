<?php

namespace SilverStripe\TagField\Tests;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\TagField\ReadonlyStringTagField;
use SilverStripe\TagField\StringTagField;
use SilverStripe\TagField\Tests\Stub\StringTagFieldTestBlogPost;

/**
 * @mixin PHPUnit_Framework_TestCase
 */
class StringTagFieldTest extends SapphireTest
{
    /**
     * @var string
     */
    protected static $fixture_file = 'StringTagFieldTest.yml';

    /**
     * @var array
     */
    protected static $extra_dataobjects = array(
        StringTagFieldTestBlogPost::class,
    );

    public function testItSavesTagsOnNewRecords()
    {
        $record = $this->getNewStringTagFieldTestBlogPost('BlogPost1');

        $field = new StringTagField('Tags');
        $field->setValue(array('Tag1', 'Tag2'));
        $field->saveInto($record);

        $record->write();

        $this->assertEquals('Tag1,Tag2', $record->Tags);
    }

    /**
     * @param string $name
     *
     * @return StringTagFieldTestBlogPost
     */
    protected function getNewStringTagFieldTestBlogPost($name)
    {
        return $this->objFromFixture(
            StringTagFieldTestBlogPost::class,
            $name
        );
    }

    public function testItSavesTagsOnExistingRecords()
    {
        $record = $this->getNewStringTagFieldTestBlogPost('BlogPost1');
        $record->write();

        $field = new StringTagField('Tags');
        $field->setValue(array('Tag1', 'Tag2'));
        $field->saveInto($record);

        $this->assertEquals('Tag1,Tag2', $record->Tags);
    }

    public function testItSuggestsTags()
    {
        $record = $this->getNewStringTagFieldTestBlogPost('BlogPost2');

        $field = new StringTagField('Tags');
        $field->setRecord($record);

        /**
         * Partial tag title match.
         */
        $request = $this->getNewRequest(array('term' => 'Tag'));

        $this->assertEquals(
            '{"items":[{"id":"Tag1","text":"Tag1"},{"id":"Tag2","text":"Tag2"}]}',
            $field->suggest($request)->getBody()
        );

        /**
         * Exact tag title match.
         */
        $request = $this->getNewRequest(array('term' => 'Tag1'));

        $this->assertEquals($field->suggest($request)->getBody(), '{"items":[{"id":"Tag1","text":"Tag1"}]}');

        /**
         * Case-insensitive tag title match.
         */
        $request = $this->getNewRequest(array('term' => 'TAG1'));

        $this->assertEquals(
            '{"items":[{"id":"Tag1","text":"Tag1"}]}',
            $field->suggest($request)->getBody()
        );

        /**
         * No tag title match.
         */
        $request = $this->getNewRequest(array('term' => 'unknown'));

        $this->assertEquals(
            '{"items":[]}',
            $field->suggest($request)->getBody()
        );
    }

    /**
     * @param array $parameters
     *
     * @return HTTPRequest
     */
    protected function getNewRequest(array $parameters)
    {
        return new HTTPRequest(
            'get',
            'StringTagFieldTestController/StringTagFieldTestForm/fields/Tags/suggest',
            $parameters
        );
    }

    /**
     * Test read only fields are returned
     */
    public function testReadonlyTransformation()
    {
        $record = $this->getNewStringTagFieldTestBlogPost('BlogPost2');
        $field = new StringTagField('Tags');
        $field->setRecord($record);

        $readOnlyField = $field->performReadonlyTransformation();
        $this->assertEquals(ReadonlyStringTagField::class, get_class($readOnlyField));
        $this->assertEquals('', $readOnlyField->Value());

        $field_two = new StringTagField('Tags');
        $field_two->setSource(['Test1', 'Test2', 'Test3']);

        $field_two->setValue(['Test1', 'Test2']);
        $field_two_readonly = $field_two->performReadonlyTransformation();
        $this->assertEquals('Test1, Test2', $field_two_readonly->getFieldValue());

        // Ensure an invalid value isn't rendered
        $field_two->setValue(['Test', 'Test1']);
        $field_two_readonly = $field_two->performReadonlyTransformation();
        $this->assertEquals('Test1', $field_two_readonly->getFieldValue());

        $field_two->setValue(['Test2']);
        $field_two_readonly = $field_two->performReadonlyTransformation();
        $this->assertEquals('Test2', $field_two_readonly->getFieldValue());
    }
}
