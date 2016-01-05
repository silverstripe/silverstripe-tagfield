<?php

/**
 * @mixin PHPUnit_Framework_TestCase
 */
class TagFieldTest extends SapphireTest
{
    /**
     * @var string
     */
    public static $fixture_file = 'tagfield/tests/TagFieldTest.yml';

    /**
     * @var array
     */
    protected $extraDataObjects = array(
        'TagFieldTestBlogTag',
        'TagFieldTestBlogPost',
    );

    public function testItSavesLinksToNewTagsOnNewRecords()
    {
        $record = $this->getNewTagFieldTestBlogPost('BlogPost1');

        $field = new TagField('Tags', '', new DataList('TagFieldTestBlogTag'));
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
            'TagFieldTestBlogPost',
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

        $field = new TagField('Tags', '', new DataList('TagFieldTestBlogTag'));
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

        $field = new TagField('Tags', '', new DataList('TagFieldTestBlogTag'));
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

        $field = new TagField('Tags', '', new DataList('TagFieldTestBlogTag'));
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
        $tag2ID = $this->idFromFixture('TagFieldTestBlogTag', 'Tag2');

        // Check tags before write
        $this->compareExpectedAndActualTags(
            array('Tag1', 'Tag2'),
            $record
        );
        $this->compareTagLists(
            array('Tag1', 'Tag2'),
            TagFieldTestBlogTag::get()
        );
        $this->assertContains($tag2ID, TagFieldTestBlogTag::get()->column('ID'));

        // Write new tags
        $field = new TagField('Tags', '', new DataList('TagFieldTestBlogTag'));
        $field->setValue(array('Tag2', 'Tag3'));
        $field->saveInto($record);

        // Check only one new tag was added
        $this->compareExpectedAndActualTags(
            array('Tag2', 'Tag3'),
            $record
        );

        // Ensure that only one new dataobject was added and that tag2s id has not changed
        $this->compareTagLists(
            array('Tag1', 'Tag2', 'Tag3'),
            TagFieldTestBlogTag::get()
        );
        $this->assertContains($tag2ID, TagFieldTestBlogTag::get()->column('ID'));
    }

    public function testItSuggestsTags()
    {
        $field = new TagField('Tags', '', new DataList('TagFieldTestBlogTag'));

        /**
         * Partial tag title match.
         */
        $request = $this->getNewRequest(array('term' => 'Tag'));

        $tag1ID = $this->idFromFixture('TagFieldTestBlogTag', 'Tag1');
        $tag2ID = $this->idFromFixture('TagFieldTestBlogTag', 'Tag2');

        $this->assertEquals(
            sprintf('{"items":[{"id":%d,"text":"Tag1"},{"id":%d,"text":"Tag2"}]}', $tag1ID, $tag2ID),
            $field->suggest($request)->getBody()
        );

        /**
         * Exact tag title match.
         */
        $request = $this->getNewRequest(array('term' => 'Tag1'));

        $this->assertEquals(
            sprintf('{"items":[{"id":%d,"text":"Tag1"}]}', $tag1ID),
            $field->suggest($request)->getBody()
        );

        /**
         * Case-insensitive tag title match.
         */
        $request = $this->getNewRequest(array('term' => 'TAG1'));

        $this->assertEquals(
            sprintf('{"items":[{"id":%d,"text":"Tag1"}]}', $tag1ID),
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
        $tag1ID = $this->idFromFixture('TagFieldTestBlogTag', 'Tag1');

        /**
         * Partial tag title match.
         */
        $request = $this->getNewRequest(array('term' => 'Tag'));

        $this->assertEquals(
            sprintf('{"items":[{"id":%d,"text":"Tag1"}]}', $tag1ID),
            $field->suggest($request)->getBody()
        );

        /**
         * Exact tag title match.
         */
        $request = $this->getNewRequest(array('term' => 'Tag1'));

        $this->assertEquals(
            sprintf('{"items":[{"id":%d,"text":"Tag1"}]}', $tag1ID),
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
     * @return SS_HTTPRequest
     */
    protected function getNewRequest(array $parameters)
    {
        return new SS_HTTPRequest(
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
                $field = new TagField('Tags', '', new DataList('TagFieldTestBlogTag'))
            ),
            new FieldList()
        );

        $form->loadDataFrom(
            $this->objFromFixture('TagFieldTestBlogPost', 'BlogPost2')
        );

        $ids = TagFieldTestBlogTag::get()->map('ID', 'ID')->toArray();

        $this->assertEquals($field->Value(), $ids);
    }

    public function testItIgnoresNewTagsIfCannotCreate()
    {
        $record = new TagFieldTestBlogPost();
        $record->write();

        $tag = TagFieldTestBlogTag::get()->filter('Title', 'Tag1')->first();

        $field = new TagField('Tags', '', new DataList('TagFieldTestBlogTag'), array($tag->ID, 'Tag3'));
        $field->setCanCreate(false);
        $field->saveInto($record);

        /**
         * @var TagFieldTestBlogPost $record
         */
        $record = DataObject::get_by_id('TagFieldTestBlogPost', $record->ID);

        $this->compareExpectedAndActualTags(
            array('Tag1'),
            $record
        );
    }
}

class TagFieldTestBlogTag extends DataObject implements TestOnly
{
    /**
     * @var string
     */
    private static $default_sort = '"TagFieldTestBlogTag"."ID" ASC';

    /**
     * @var array
     */
    private static $db = array(
        'Title' => 'Varchar(200)',
    );

    /**
     * @var array
     */
    private static $belongs_many_many = array(
        'BlogPosts' => 'TagFieldTestBlogPost',
    );
}

/**
 * @method ManyManyList Tags()
 */
class TagFieldTestBlogPost extends DataObject implements TestOnly
{
    /**
     * @var array
     */
    private static $db = array(
        'Title' => 'Text',
        'Content' => 'Text',
    );

    /**
     * @var array
     */
    private static $many_many = array(
        'Tags' => 'TagFieldTestBlogTag',
    );
}

class TagFieldTestController extends Controller implements TestOnly
{
    /**
     * @return Form
     */
    public function TagFieldTestForm()
    {
        $fields = new FieldList(
            $tagField = new TagField('Tags', '', new DataList('TagFieldTestBlogTag'))
        );

        $actions = new FieldList(
            new FormAction('TagFieldTestFormSubmit')
        );

        return new Form($this, 'TagFieldTestForm', $fields, $actions);
    }

    /**
     * @param DataObject $dataObject
     * @param Form $form
     */
    public function TagFieldTestFormSubmit(DataObject $dataObject, Form $form)
    {
        $form->saveInto($dataObject);
    }
}
