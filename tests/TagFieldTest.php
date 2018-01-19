<?php

namespace SilverStripe\TagField\Tests;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\TagField\ReadonlyTagField;
use SilverStripe\TagField\TagField;
use SilverStripe\TagField\Tests\Stub\TagFieldTestBlogPost;
use SilverStripe\TagField\Tests\Stub\TagFieldTestBlogTag;
use SilverStripe\TagField\Tests\Stub\TagFieldTestController;
use SilverStripe\View\ArrayData;

/**
 * @mixin PHPUnit_Framework_TestCase
 */
class TagFieldTest extends SapphireTest
{
    /**
     * @var string
     */
    protected static $fixture_file = 'TagFieldTest.yml';

    /**
     * @var array
     */
    protected static $extra_dataobjects = [
        TagFieldTestBlogTag::class,
        TagFieldTestBlogPost::class,
    ];

    public function testItSavesLinksToNewTagsOnNewRecords()
    {
        $record = $this->getNewTagFieldTestBlogPost('BlogPost1');
        $field = new TagField('Tags', '', new DataList(TagFieldTestBlogTag::class));
        $field->setValue(array('Tag3', 'Tag4'));
        $field->saveInto($record);
        $record->write();
        $this->compareExpectedAndActualTags(
            array('Tag3', 'Tag4'),
            $record
        );
    }

    /**
     * @param string $name
     *
     * @return TagFieldTestBlogPost
     */
    protected function getNewTagFieldTestBlogPost($name)
    {
        return $this->objFromFixture(
            TagFieldTestBlogPost::class,
            $name
        );
    }

    /**
     * @param array $expected
     * @param TagFieldTestBlogPost $record
     */
    protected function compareExpectedAndActualTags(array $expected, TagFieldTestBlogPost $record)
    {
        $this->compareTagLists($expected, $record->Tags());
    }

    /**
     * Ensure a source of tags matches the given string tag names
     *
     * @param array $expected
     * @param DataList $actualSource
     */
    protected function compareTagLists(array $expected, DataList $actualSource)
    {
        $actual = array_values($actualSource->map('ID', 'Title')->toArray());
        sort($expected);
        sort($actual);

        $this->assertEquals(
            $expected,
            $actual
        );
    }

    public function testItSavesLinksToNewTagsOnExistingRecords()
    {
        $record = $this->getNewTagFieldTestBlogPost('BlogPost1');
        $record->write();

        $field = new TagField('Tags', '', new DataList(TagFieldTestBlogTag::class));
        $field->setValue(array('Tag3', 'Tag4'));
        $field->saveInto($record);

        $this->compareExpectedAndActualTags(
            array('Tag3', 'Tag4'),
            $record
        );
    }

    public function testItSavesLinksToExistingTagsOnNewRecords()
    {
        $record = $this->getNewTagFieldTestBlogPost('BlogPost1');

        $field = new TagField('Tags', '', new DataList(TagFieldTestBlogTag::class));
        $field->setValue(array('Tag1', 'Tag2'));
        $field->saveInto($record);

        $record->write();

        $this->compareExpectedAndActualTags(
            array('Tag1', 'Tag2'),
            $record
        );
    }

    public function testItSavesLinksToExistingTagsOnExistingRecords()
    {
        $record = $this->getNewTagFieldTestBlogPost('BlogPost1');
        $record->write();

        $field = new TagField('Tags', '', new DataList(TagFieldTestBlogTag::class));
        $field->setValue(array('Tag1', 'Tag2'));
        $field->saveInto($record);

        $this->compareExpectedAndActualTags(
            array('Tag1', 'Tag2'),
            $record
        );
    }

    /**
     * Ensure that {@see TagField::saveInto} respects existing tags
     */
    public function testSaveDuplicateTags()
    {
        $record = $this->getNewTagFieldTestBlogPost('BlogPost2');
        $record->write();
        $tag2ID = $this->idFromFixture(TagFieldTestBlogTag::class, 'Tag2');

        // Check tags before write
        $this->compareExpectedAndActualTags(
            array('Tag1', '222'),
            $record
        );
        $this->compareTagLists(
            array('Tag1', '222'),
            TagFieldTestBlogTag::get()
        );
        $this->assertContains($tag2ID, TagFieldTestBlogTag::get()->column('ID'));

        // Write new tags
        $field = new TagField('Tags', '', new DataList(TagFieldTestBlogTag::class));
        $field->setValue(array('222', 'Tag3'));
        $field->saveInto($record);

        // Check only one new tag was added
        $this->compareExpectedAndActualTags(
            array('222', 'Tag3'),
            $record
        );

        // Ensure that only one new dataobject was added and that tag2s id has not changed
        $this->compareTagLists(
            array('Tag1', '222', 'Tag3'),
            TagFieldTestBlogTag::get()
        );
        $this->assertContains($tag2ID, TagFieldTestBlogTag::get()->column('ID'));
    }

    public function testItSuggestsTags()
    {
        $field = new TagField('Tags', '', new DataList(TagFieldTestBlogTag::class));

        /**
         * Partial tag title match.
         */
        $request = $this->getNewRequest(array('term' => 'Tag'));

        $this->assertEquals(
            '{"items":[{"id":"Tag1","text":"Tag1"}]}',
            $field->suggest($request)->getBody()
        );

        /**
         * Exact tag title match.
         */
        $request = $this->getNewRequest(array('term' => '222'));

        $this->assertEquals(
            '{"items":[{"id":"222","text":"222"}]}',
            $field->suggest($request)->getBody()
        );

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
     * Tests that TagField supports pre-filtered data sources
     */
    public function testRestrictedSuggestions()
    {
        $source = TagFieldTestBlogTag::get()->exclude('Title', 'Tag2');
        $field = new TagField('Tags', '', $source);

        /**
         * Partial tag title match.
         */
        $request = $this->getNewRequest(array('term' => 'Tag'));

        $this->assertEquals(
            '{"items":[{"id":"Tag1","text":"Tag1"}]}',
            $field->suggest($request)->getBody()
        );

        /**
         * Exact tag title match.
         */
        $request = $this->getNewRequest(array('term' => 'Tag1'));

        $this->assertEquals(
            '{"items":[{"id":"Tag1","text":"Tag1"}]}',
            $field->suggest($request)->getBody()
        );

        /**
         * Excluded item doesn't appear in matches
         */
        $request = $this->getNewRequest(array('term' => 'Tag2'));

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
            'TagFieldTestController/TagFieldTestForm/fields/Tags/suggest',
            $parameters
        );
    }

    public function testItDisplaysValuesFromRelations()
    {
        $record = $this->getNewTagFieldTestBlogPost('BlogPost1');
        $record->write();

        $form = new Form(
            new TagFieldTestController($record),
            'Form',
            new FieldList(
                $field = new TagField('Tags', '', new DataList(TagFieldTestBlogTag::class))
            ),
            new FieldList()
        );

        $form->loadDataFrom(
            $this->objFromFixture(TagFieldTestBlogPost::class, 'BlogPost2')
        );

        $ids = TagFieldTestBlogTag::get()->column('Title');

        $this->assertEquals($field->Value(), $ids);
    }

    public function testItIgnoresNewTagsIfCannotCreate()
    {
        $this->markTestSkipped(
            'This test has not been updated yet.'
        );

        $record = new TagFieldTestBlogPost();
        $record->write();

        $tag = TagFieldTestBlogTag::get()->filter('Title', 'Tag1')->first();

        $field = new TagField('Tags', '', new DataList(TagFieldTestBlogTag::class), array($tag->Title, 'Tag3'));
        $field->setCanCreate(false);
        $field->saveInto($record);

        /**
         * @var TagFieldTestBlogPost $record
         */
        $record = DataObject::get_by_id(TagFieldTestBlogPost::class, $record->ID);

        $this->compareExpectedAndActualTags(
            array('Tag1'),
            $record
        );
    }

    /**
     * Test you can save without a source set
     */
    public function testSaveEmptySource()
    {
        $record = new TagFieldTestBlogPost();
        $record->write();

        // Clear database of tags
        TagFieldTestBlogTag::get()->removeAll();

        $field = new TagField('Tags', '', TagFieldTestBlogTag::get());
        $field->setValue(['New Tag']);
        $field->setCanCreate(true);
        $field->saveInto($record);

        $tag = TagFieldTestBlogTag::get()->first();
        $this->assertNotEmpty($tag);
        $this->assertEquals('New Tag', $tag->Title);
        $record = TagFieldTestBlogPost::get()->byID($record->ID);
        $this->assertEquals(
            $tag->ID,
            $record->Tags()->first()->ID
        );
    }


    /**
     * Test read only fields are returned
     */
    public function testReadonlyTransformation()
    {
        $field = new TagField('Tags', '', TagFieldTestBlogTag::get());
        $readOnlyField = $field->performReadonlyTransformation();
        $this->assertEquals(ReadonlyTagField::class, get_class($readOnlyField));

    }
}
