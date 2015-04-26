<?php

class TagFieldTest extends SapphireTest {
	/**
	 * @var string
	 */
	public static $fixture_file = 'tagfield/tests/unit/TagFieldTest.yml';

	/**
	 * @var array
	 */
	protected $extraDataObjects = array(
		'TagFieldTest_BlogTag',
		'TagFieldTest_BlogPost',
	);

	public function testItSavesLinksToExistingTagsOnExistingRecords() {
		$record = $this->objFromFixture(
			'TagFieldTest_BlogPost',
			'BlogPost1'
		);

		$field = new TagField('Tags');
		$field->setValue(array('Object1', 'Object2'));
		$field->saveInto($record);

		$record->write();

		$this->compareExpectedAndActualTags(
			$record,
			array('Object1', 'Object2')
		);
	}

	/**
	 * @param DataObject $record
	 * @param array $expected
	 */
	protected function compareExpectedAndActualTags(DataObject $record, array $expected) {
		$compare1 = array_values($record->Tags()->map('ID', 'Title')->toArray());
		$compare2 = $expected;

		sort($compare1);
		sort($compare2);

		$this->assertEquals(
			$compare1,
			$compare2
		);
	}

	public function testItSavesLinksToExistingTagsOnNewRecords() {
		$record = new TagFieldTest_BlogPost();
		$record->write();

		$field = new TagField('Tags');
		$field->setValue(array('Object1', 'Object2'));
		$field->saveInto($record);

		$record->write();

		$this->compareExpectedAndActualTags(
			$record,
			array('Object1', 'Object2')
		);
	}

	public function testItSavesLinksToNewTagsOnExistingRecords() {
		$record = $this->objFromFixture(
			'TagFieldTest_BlogPost',
			'BlogPost1'
		);

		$field = new TagField('Tags');
		$field->setValue(array('Object3', 'Object4'));
		$field->saveInto($record);

		$record->write();

		$this->compareExpectedAndActualTags(
			$record,
			array('Object3', 'Object4')
		);
	}

	function testItSavesLinksToNewTagsOnNewRecords() {
		$record = new TagFieldTest_BlogPost();
		$record->write();

		$field = new TagField('Tags');
		$field->setValue(array('Object3', 'Object4'));
		$field->saveInto($record);

		$this->compareExpectedAndActualTags(
			$record,
			array('Object3', 'Object4')
		);
	}

	function testItSavesTextBasedTagsOnExistingRecords() {
		$record = $this->objFromFixture(
			'TagFieldTest_BlogPost',
			'BlogPost1'
		);

		$field = new TagField('TextBasedTags');
		$field->setValue(array('Text1', 'Text2'));
		$field->saveInto($record);

		$record->write();

		$this->assertEquals(
			$record->TextBasedTags,
			'Text1,Text2'
		);
	}

	function testItSavesTextBasedTagsOnNewRecords() {
		$record = new TagFieldTest_BlogPost();
		$record->write();

		$field = new TagField('TextBasedTags');
		$field->setValue(array('Text1', 'Text2'));
		$field->saveInto($record);

		$record->write();

		$this->assertEquals(
			$record->TextBasedTags,
			'Text1,Text2'
		);
	}

	function testItSuggestsObjectTags() {
		$field = new TagField('Tags');
		$field->setRecordClass('TagFieldTest_BlogPost');

		/**
		 * Partial tag title match.
		 */
		$request = new SS_HTTPRequest(
			'get',
			'TagFieldTest_Controller/ObjectTestForm/fields/Tags/suggest',
			array('term' => 'Object')
		);

		$this->assertEquals($field->suggest($request)->getBody(), '{"items":[{"id":1,"text":"Object1"},{"id":2,"text":"Object2"}]}');

		/**
		 * Exact tag title match.
		 */
		$request = new SS_HTTPRequest(
			'get',
			'TagFieldTest_Controller/ObjectTestForm/fields/Tags/suggest',
			array('term' => 'Object1')
		);

		$this->assertEquals($field->suggest($request)->getBody(), '{"items":[{"id":1,"text":"Object1"}]}');

		/**
		 * Case-insensitive tag title match.
		 */
		$request = new SS_HTTPRequest(
			'get',
			'TagFieldTest_Controller/ObjectTestForm/fields/Tags/suggest',
			array('term' => 'OBJECT1')
		);
		$this->assertEquals($field->suggest($request)->getBody(), '{"items":[{"id":1,"text":"Object1"}]}');

		/**
		 * No tag title match.
		 */
		$request = new SS_HTTPRequest(
			'get',
			'TagFieldTest_Controller/ObjectTestForm/fields/Tags/suggest',
			array('term' => 'unknown')
		);

		$this->assertEquals($field->suggest($request)->getBody(), '{"items":[]}');
	}

	function testItSuggestsTextTags() {
		$field = new TagField('TextBasedTags');
		$field->setRecordClass('TagFieldTest_BlogPost');

		/**
		 * Partial tag title match.
		 */
		$request = new SS_HTTPRequest(
			'get',
			'TagFieldTest_Controller/TextBasedTestForm/fields/Tags/suggest',
			array('term' => 'Text')
		);

		$this->assertEquals($field->suggest($request)->getBody(), '{"items":[{"id":"Text1","text":"Text1"},{"id":"Text2","text":"Text2"}]}');

		/**
		 * Exact tag title match.
		 */
		$request = new SS_HTTPRequest(
			'get',
			'TagFieldTest_Controller/TextBasedTestForm/fields/Tags/suggest',
			array('term' => 'Text1')
		);

		$this->assertEquals($field->suggest($request)->getBody(), '{"items":[{"id":"Text1","text":"Text1"}]}');

		/**
		 * Case-insensitive tag title match.
		 */
		$request = new SS_HTTPRequest(
			'get',
			'TagFieldTest_Controller/TextBasedTestForm/fields/Tags/suggest',
			array('term' => 'TEXT1')
		);

		$this->assertEquals($field->suggest($request)->getBody(), '{"items":[{"id":"Text1","text":"Text1"}]}');

		/**
		 * No tag title match.
		 */
		$request = new SS_HTTPRequest(
			'get',
			'TagFieldTest_Controller/TextBasedTestForm/fields/Tags/suggest',
			array('term' => 'unknown')
		);

		$this->assertEquals($field->suggest($request)->getBody(), '{"items":[]}');
	}

	function testItDisplaysValuesFromRelations() {
		$form = new Form(
			$this,
			'Form',
			new FieldList(
				$field = new TagField('Tags')
			),
			new FieldList()
		);

		$form->loadDataFrom(
			$this->objFromFixture('TagFieldTest_BlogPost', 'BlogPost3')
		);

		$this->assertEquals($field->Value(), array(1 => 1, 2 => 2));
	}

	function testItIgnoresNewTagsIfReadOnly() {
		$record = new TagFieldTest_BlogPost();
		$record->write();

		$tag = TagFieldTest_BlogTag::get()->filter('Title', 'Object1')->first();

		$field = new TagField('Tags');
		$field->setReadOnly(true);
		$field->setValue(array($tag->ID, 'Object3'));
		$field->saveInto($record);

		$record = DataObject::get_by_id('TagFieldTest_BlogPost', $record->ID);

		$this->compareExpectedAndActualTags(
			$record,
			array('Object1')
		);
	}
}

class TagFieldTest_BlogTag extends DataObject implements TestOnly {
	/**
	 * @var string
	 */
	private static $default_sort = '"TagFieldTest_BlogTag"."ID" ASC';

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
		'BlogEntries' => 'TagFieldTest_BlogPost',
	);
}

class TagFieldTest_BlogPost extends DataObject implements TestOnly {
	/**
	 * @var string
	 */
	private static $default_sort = '"TagFieldTest_BlogPost"."ID" ASC';

	/**
	 * @var array
	 */
	private static $db = array(
		'Title' => 'Text',
		'Content' => 'Text',
		'TextBasedTags' => 'Text',
	);

	/**
	 * @var array
	 */
	private static $many_many = array(
		'Tags' => 'TagFieldTest_BlogTag',
	);
}

class TagFieldTest_Controller extends Controller implements TestOnly {
	/**
	 * @var array
	 */
	private static $url_handlers = array(
		'$Action//$ID/$OtherID' => "handleAction",
	);

	/**
	 * @return Form
	 */
	public function ObjectTestForm() {
		$fields = new FieldList(
			$tagField = new TagField('Tags')
		);

		$actions = new FieldList(
			new FormAction('ObjectTestForm_submit')
		);

		return new Form($this, 'ObjectTestForm', $fields, $actions);
	}

	/**
	 * @param array $data
	 * @param Form $form
	 */
	public function ObjectTestForm_submit(array $data, Form $form) {
		$data->saveInto($form);
	}

	/**
	 * @return Form
	 */
	public function TextBasedTestForm() {
		$fields = new FieldList(
			$tagField = new TagField('TextBasedTags')
		);

		$actions = new FieldList(
			new FormAction('TextBasedTestForm_submit')
		);

		return new Form($this, 'TextBasedTestForm', $fields, $actions);
	}

	/**
	 * @param array $data
	 * @param Form $form
	 */
	public function TextBasedTestForm_submit(array $data, Form $form) {
		$data->saveInto($form);
	}
}
