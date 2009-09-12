<?php
/**
 * @author Ingo Schommer, SilverStripe Ltd. (<firstname>@silverstripe.com)
 * @package testing
 * 
 * @todo Test filtering and sorting
 * @todo Test custom tags
 * @todo Test custom separators
 */
class TagFieldTest extends FunctionalTest {
	
	static $fixture_file = 'tagfield/tests/TagFieldTest.yml';
	
	function testExistingObjectSaving() {
		// should contain "tag1" and "tag2"
		$existingEntry = $this->objFromFixture('TagFieldTest_BlogEntry', 'blogentry1');
		$field = new TagField('Tags', null, null, 'TagFieldTest_BlogEntry');
		$field->setValue('tag1   tag3 ');
		$field->saveInto($existingEntry);
		$existingEntry->write();
		$this->assertEquals(
			array_values($existingEntry->Tags()->map('ID', 'Title')),
			array('tag1','tag3')
		);
	}
	
	function testNewObjectSaving() {
		$newEntry = new TagFieldTest_BlogEntry();
		$newEntry->write();
		$field = new TagField('Tags', null, null, 'TagFieldTest_BlogEntry');
		$field->setValue('tag1 tag2'); // test separator handling as well
		$field->saveInto($newEntry);

		$this->assertEquals(
			array_values($newEntry->Tags()->map('ID', 'Title')),
			array('tag1','tag2')
		);
	}
	
	function testTextbasedSaving() {
		// should contain "tag1" and "tag2"
		$existingEntry = $this->objFromFixture('TagFieldTest_BlogEntry', 'blogentry1');
		$field = new TagField('TextbasedTags', null, null, 'TagFieldTest_BlogEntry');
		$field->setValue('tag1   tag3 '); // test separator handling as well
		$field->saveInto($existingEntry);
		$existingEntry->write();
		$this->assertEquals(
			$existingEntry->TextbasedTags,
			'tag1 tag3'
		);
	}
	/*
	function testSuggestRequest() {
		// partial
		$response = $this->post('TagFieldTestController/ObjectTestForm/fields/Tags/suggest', array('tag','tag'));
		$this->assertEquals($response->getBody(), '["tag1","tag2"]');
	}
	*/
	function testObjectSuggest() {
		$field = new TagField('Tags', null, null, 'TagFieldTest_BlogEntry');
		
		// partial
		$request = new HTTPRequest(
			'get',
			'TagFieldTestController/ObjectTestForm/fields/Tags/suggest', 
			null,
			array('tag' => 'tag')
		);
		$this->assertEquals($field->suggest($request), '["tag1","tag2"]');
		
		// full
		$request = new HTTPRequest(
			'get',
			'TagFieldTestController/ObjectTestForm/fields/Tags/suggest',
			null,
			array('tag' => 'tag1')
		);
		
		$this->assertEquals($field->suggest($request), '["tag1"]');
		
		// case insensitive
		$request = new HTTPRequest(
			'get',
			'TagFieldTestController/ObjectTestForm/fields/Tags/suggest',
			null,
			array('tag' => 'TAG1')
		);
		$this->assertEquals($field->suggest($request), '["tag1"]');
		
		// no match
		$request = new HTTPRequest(
			'get',
			'TagFieldTestController/ObjectTestForm/fields/Tags/suggest',
			null,
			array('tag' => 'unknown')
		);
		$this->assertEquals($field->suggest($request), '[]');
	}
	
	function testTextbasedSuggest() {
		$field = new TagField('TextbasedTags', null, null, 'TagFieldTest_BlogEntry');
		
		// partial
		$request = new HTTPRequest(
			'get',
			'TagFieldTestController/TextbasedTestForm/fields/Tags/suggest',
			null,
			array('tag' => 'tag')
		);
		$this->assertEquals($field->suggest($request), '["textbasedtag1","textbasedtag2"]');
		
		// full
		$request = new HTTPRequest(
			'get',
			'TagFieldTestController/TextbasedTestForm/fields/Tags/suggest',
			null,
			array('tag' => 'tag1')
		);
		$this->assertEquals($field->suggest($request), '["textbasedtag1"]');
		
		// case insensitive
		$request = new HTTPRequest(
			'get',
			'TagFieldTestController/TextbasedTestForm/fields/Tags/suggest',
			null,
			array('tag' => 'TAG1')
		);
		$this->assertEquals($field->suggest($request), '["textbasedtag1"]');
		
		// no match
		$request = new HTTPRequest(
			'get',
			'TagFieldTestController/TextbasedTestForm/fields/Tags/suggest',
			null,
			array('tag' => 'unknown')
		);
		$this->assertEquals($field->suggest($request), '[]');
	}
	
	function testValueDisplayFromRelation() {
		$form = new Form(
			$this,
			'Form',
			new FieldSet(
				$field = new TagField('Tags', null, null, 'TagFieldTest_BlogEntry')
			),
			new FieldSet()
		);
		$existingEntry = $this->objFromFixture('TagFieldTest_BlogEntry', 'blogentry1');
		$form->loadDataFrom($existingEntry);
		$this->assertEquals($field->Value(), 'tag1 tag2', 'Correctly displays saved relationships');
	}

	function testRemoveUnusedTagsEnabled() {
		// should contain "tag1" and "tag2"
		$entry1 = $this->objFromFixture('TagFieldTest_BlogEntry', 'blogentry1');
		
		// should contain "tag1"
		$entry2 = $this->objFromFixture('TagFieldTest_BlogEntry', 'blogentry2');
		
		$field = new TagField('Tags', null, null, 'TagFieldTest_BlogEntry');
		$field->setValue('tag3');
		$field->saveInto($entry1);
		$entry1->write();
		
		$this->assertType(
			'TagFieldTest_Tag', 
			DataObject::get_one('TagFieldTest_Tag', '`TagFieldTest_Tag`.`Title` = \'tag1\''),
			'Removing a tag relation which is still present in other objects shouldnt remove the tag record'
		);
		$this->assertFalse(
			DataObject::get_one('TagFieldTest_Tag', '`TagFieldTest_Tag`.`Title` = \'tag2\''),
			'If the only remaining relation of a tag record is removed, the tag should be removed completely'
		);
	}
	
	function testRemoveUnusedTagsDisabled() {
		// should contain "tag1" and "tag2"
		$entry1 = $this->objFromFixture('TagFieldTest_BlogEntry', 'blogentry1');
		
		// should contain "tag1"
		$entry2 = $this->objFromFixture('TagFieldTest_BlogEntry', 'blogentry2');
		
		$field = new TagField('Tags', null, null, 'TagFieldTest_BlogEntry');
		$field->deleteUnusedTags = false;
		$field->setValue('tag3');
		$field->saveInto($entry1);
		$entry1->write();
		
		$this->assertType(
			'TagFieldTest_Tag', 
			DataObject::get_one('TagFieldTest_Tag', '`TagFieldTest_Tag`.`Title` = \'tag2\''),
			'If the only remaining relation of a tag record is removed and $deleteUnusedTags is disabled, the tag record should be retained'
		);
	}
	
}

class TagFieldTest_Tag extends DataObject implements TestOnly {
	
	static $db = array(
		'Title' => 'Varchar(200)'
	);
	
	static $belongs_many_many = array(
		'BlogEntries' => 'TagFieldTest_BlogEntry'
	);
	
}

class TagFieldTest_BlogEntry extends DataObject implements TestOnly {
	
	static $db = array(
		'Title' => 'Text',
		'Content' => 'Text',
		'TextbasedTags' => 'Text'
	);
	
	static $many_many = array(
		'Tags' => 'TagFieldTest_Tag'
	);
	
}

class TagFieldTest_Controller extends Controller {
	
	static $url_handlers = array(
		// The double-slash is need here to ensure that 
		'$Action//$ID/$OtherID' => "handleAction",
	);
	
	public function ObjectTestForm() {
		$fields = new FieldSet(
			$tagField = new TagField('Tags', null, null, 'TagFieldTest_BlogEntry')
		);
		$actions = new FieldSet(
			new FormAction('ObjectTestForm_submit')
		);
		$form = new Form($this, 'ObjectTestForm', $fields, $actions);
		
		return $form;
	}
	
	public function ObjectTestForm_submit($data, $form) {
		$data->saveInto($form);
	}
	
	public function TextbasedTestForm() {
		$fields = new FieldSet(
			$tagField = new TagField('TextbasedTags', null, null, 'TagFieldTest_BlogEntry')
		);
		$actions = new FieldSet(
			new FormAction('TextbasedTestForm_submit')
		);
		$form = new Form($this, 'TextbasedTestForm', $fields, $actions);
		
		return $form;
	}
	
	public function TextbasedTestForm_submit($data, $form) {
		$data->saveInto($form);
	}
	
}

Director::addRules(50, array(
	'TagFieldTestController' => "TagFieldTest_Controller",
));
?>