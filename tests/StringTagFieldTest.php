<?php

/**
 * @mixin PHPUnit_Framework_TestCase
 */
class StringTagFieldTest extends SapphireTest {
	/**
	 * @var string
	 */
	public static $fixture_file = 'tagfield/tests/StringTagFieldTest.yml';

	/**
	 * @var array
	 */
	protected $extraDataObjects = array(
		'StringTagFieldTestBlogPost',
	);

	function testItSavesTagsOnNewRecords() {
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
	protected function getNewStringTagFieldTestBlogPost($name) {
		return $this->objFromFixture(
			'StringTagFieldTestBlogPost',
			$name
		);
	}

	function testItSavesTagsOnExistingRecords() {
		$record = $this->getNewStringTagFieldTestBlogPost('BlogPost1');
		$record->write();

		$field = new StringTagField('Tags');
		$field->setValue(array('Tag1', 'Tag2'));
		$field->saveInto($record);

		$this->assertEquals('Tag1,Tag2', $record->Tags);
	}

	function testItSuggestsTags() {
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
	 * @return SS_HTTPRequest
	 */
	protected function getNewRequest(array $parameters) {
		return new SS_HTTPRequest(
			'get',
			'StringTagFieldTestController/StringTagFieldTestForm/fields/Tags/suggest',
			$parameters
		);
	}
}

/**
 * @property string $Tags
 */
class StringTagFieldTestBlogPost extends DataObject implements TestOnly {
	/**
	 * @var array
	 */
	private static $db = array(
		'Title' => 'Text',
		'Content' => 'Text',
		'Tags' => 'Text',
	);
}

class StringTagFieldTestController extends Controller implements TestOnly {
	/**
	 * @return Form
	 */
	public function StringTagFieldTestForm() {
		$fields = new FieldList(
			$tagField = new StringTagField('Tags')
		);

		$actions = new FieldList(
			new FormAction('StringTagFieldTestFormSubmit')
		);

		return new Form($this, 'StringTagFieldTestForm', $fields, $actions);
	}

	/**
	 * @param DataObject $dataObject
	 * @param Form $form
	 */
	public function StringTagFieldTestFormSubmit(DataObject $dataObject, Form $form) {
		$form->saveInto($dataObject);
	}
}
