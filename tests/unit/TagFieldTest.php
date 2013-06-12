<?php
/**
 * @author Ingo Schommer, SilverStripe Ltd. (<firstname>@silverstripe.com)
 * @package testing
 * 
 * @todo Test custom tags
 */
class TagFieldTest extends SapphireTest {
	
	static $fixture_file = 'tagfield/tests/unit/TagFieldTest.yml';

	protected $extraDataObjects = array(
		'TagFieldTest_Tag',
		'TagFieldTest_BlogEntry',
	);
	
	function testExistingObjectSaving() {
		// should contain "tag1" and "tag2"
		$existingEntry = $this->objFromFixture('TagFieldTest_BlogEntry', 'blogentry1');
		$field = new TagField('Tags', null, null, 'TagFieldTest_BlogEntry');
		$field->setValue('tag1   tag3 ');
		$field->saveInto($existingEntry);
		$existingEntry->write();
		
		$compare1=array_values($existingEntry->Tags()->map('ID', 'Title')->toArray());
		$compare2=array('tag1','tag3');
		sort($compare1);
		sort($compare2);
		
		$this->assertEquals(
			$compare1,
			$compare2
		);
	}
	
	function testNewObjectSaving() {
		$newEntry = new TagFieldTest_BlogEntry();
		$newEntry->write();
		$field = new TagField('Tags', null, null, 'TagFieldTest_BlogEntry');
		$field->setValue('tag1 tag2'); // test separator handling as well
		$field->saveInto($newEntry);
	
		$compare1=array_values($newEntry->Tags()->map('ID', 'Title')->toArray());
		$compare2=array('tag1','tag2');
		sort($compare1);
		sort($compare2);
		$this->assertEquals(
			$compare1,
			$compare2
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
		$response = $this->post('TagFieldTest_Controller/ObjectTestForm/fields/Tags/suggest', array('tag','tag'));
		$this->assertEquals($response->getBody(), '["tag1","tag2"]');
	}
	*/
	
	function testObjectSuggest() {
		$field = new TagField('Tags', null, null, 'TagFieldTest_BlogEntry');
	
		// backwards compatibility change
		$httpReqClass = class_exists('SS_HTTPRequest') ? 'SS_HTTPRequest' : 'HTTPRequest';
		
		// partial
		$request = new $httpReqClass(
			'get',
			'TagFieldTest_Controller/ObjectTestForm/fields/Tags/suggest', 
			null,
			array('tag' => 'tag')
		);
		$this->assertEquals($field->suggest($request), '["tag1","tag2"]');
		
		// full
		$request = new $httpReqClass(
			'get',
			'TagFieldTest_Controller/ObjectTestForm/fields/Tags/suggest',
			null,
			array('tag' => 'tag1')
		);
		
		$this->assertEquals($field->suggest($request), '["tag1"]');
		
		// case insensitive
		$request = new $httpReqClass(
			'get',
			'TagFieldTest_Controller/ObjectTestForm/fields/Tags/suggest',
			null,
			array('tag' => 'TAG1')
		);
		$this->assertEquals($field->suggest($request), '["tag1"]');
		
		// no match
		$request = new $httpReqClass(
			'get',
			'TagFieldTest_Controller/ObjectTestForm/fields/Tags/suggest',
			null,
			array('tag' => 'unknown')
		);
		$this->assertEquals($field->suggest($request), '[]');
	}
	
	function testTextbasedSuggest() {
		$field = new TagField('TextbasedTags', null, null, 'TagFieldTest_BlogEntry');
	
		// backwards compatibility change
		$httpReqClass = class_exists('SS_HTTPRequest') ? 'SS_HTTPRequest' : 'HTTPRequest';
		
		// partial
		$request = new $httpReqClass(
			'get',
			'TagFieldTest_Controller/TextbasedTestForm/fields/Tags/suggest',
			null,
			array('tag' => 'tag')
		);
		$this->assertEquals($field->suggest($request), '["textbasedtag1","textbasedtag2"]');
		
		// full
		$request = new $httpReqClass(
			'get',
			'TagFieldTest_Controller/TextbasedTestForm/fields/Tags/suggest',
			null,
			array('tag' => 'tag1')
		);
		$this->assertEquals($field->suggest($request), '["textbasedtag1"]');
		
		// case insensitive
		$request = new $httpReqClass(
			'get',
			'TagFieldTest_Controller/TextbasedTestForm/fields/Tags/suggest',
			null,
			array('tag' => 'TAG1')
		);
		$this->assertEquals($field->suggest($request), '["textbasedtag1"]');
		
		// no match
		$request = new $httpReqClass(
			'get',
			'TagFieldTest_Controller/TextbasedTestForm/fields/Tags/suggest',
			null,
			array('tag' => 'unknown')
		);
		$this->assertEquals($field->suggest($request), '[]');
	}
	
	function testValueDisplayFromRelation() {
		$form = new Form(
			$this,
			'Form',
			new FieldList(
				$field = new TagField('Tags', null, null, 'TagFieldTest_BlogEntry')
			),
			new FieldList()
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
		
		$this->assertInstanceOf(
			'TagFieldTest_Tag', 
			TagFieldTest_Tag::get()->filter('Title', 'tag1')->First(),
			'Removing a tag relation which is still present in other objects shouldnt remove the tag record'
		);
		$this->assertNull(
			TagFieldTest_Tag::get()->filter('Title', 'tag2')->First(),
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
		
		$q = defined('DB::USE_ANSI_SQL') ? '"' : '`';
		$this->assertInstanceOf(
			'TagFieldTest_Tag', 
			DataObject::get_one('TagFieldTest_Tag', "{$q}TagFieldTest_Tag{$q}.{$q}Title{$q} = 'tag2'"),
			'If the only remaining relation of a tag record is removed and $deleteUnusedTags is disabled, the tag record should be retained'
		);
	}
	
	function testCreateNewTagsDisabled() {
		$newEntry = new TagFieldTest_BlogEntry();
		$newEntry->write();
		$field = new TagField('Tags', null, null, 'TagFieldTest_BlogEntry');
		$field->createNewTags = false;
		$field->setValue('tag1 newtag2'); //'tag1' is from the fixture already, 'newtag2' is not in the db so shouldn't be added as a tag		
		$field->saveInto($newEntry);		
		
		$savedEntry = DataObject::get_by_id('TagFieldTest_BlogEntry', $newEntry->ID);
		$compare1=array_values($newEntry->Tags()->map('ID', 'Title')->toArray());
		$compare2=array('tag1');
		sort($compare1);
		sort($compare2);
		$this->assertEquals(
			$compare1,
			$compare2
		);
	}
	
	function testCustomSeparators() {
		// should contain "tag1" and "tag2"
		$existingEntry = $this->objFromFixture('TagFieldTest_BlogEntry', 'blogentry1');
		$field = new TagField('Tags', null, null, 'TagFieldTest_BlogEntry');
		$field->setSeparator(',');
		$field->setValue('tag1,tag3');
		$field->saveInto($existingEntry);
		$existingEntry->write();
		
		$compare1=array_values($existingEntry->Tags()->map('ID', 'Title')->toArray());
		$compare2=array('tag1','tag3');
		sort($compare1);
		sort($compare2);
		
		$this->assertEquals(
			$compare1,
			$compare2
		);
	}
	
	function testSuggestWithTagSort() {
		$field = new TagField('Tags', null, null, 'TagFieldTest_BlogEntry');
		$field->setTagSort('"TagFieldTest_Tag"."Title" DESC');
		
		// backwards compatibility change
		$httpReqClass = class_exists('SS_HTTPRequest') ? 'SS_HTTPRequest' : 'HTTPRequest';
		
		// partial
		$request = new $httpReqClass(
			'get',
			'TagFieldTest_Controller/ObjectTestForm/fields/Tags/suggest', 
			null,
			array('tag' => 'tag')
		);
		$this->assertEquals($field->suggest($request), '["tag2","tag1"]');
	}
}

class TagFieldTest_Tag extends DataObject implements TestOnly {
	
	private static $default_sort = '"TagFieldTest_Tag"."ID" ASC';
	
	private static $db = array(
		'Title' => 'Varchar(200)'
	);
	
	private static $belongs_many_many = array(
		'BlogEntries' => 'TagFieldTest_BlogEntry'
	);
	
}

class TagFieldTest_BlogEntry extends DataObject implements TestOnly {
	
	private static $default_sort = '"TagFieldTest_BlogEntry"."ID" ASC';
	
	private static $db = array(
		'Title' => 'Text',
		'Content' => 'Text',
		'TextbasedTags' => 'Text'
	);
	
	private static $many_many = array(
		'Tags' => 'TagFieldTest_Tag'
	);
	
}

class TagFieldTest_Controller extends Controller {
	
	private static $url_handlers = array(
		// The double-slash is need here to ensure that 
		'$Action//$ID/$OtherID' => "handleAction",
	);
	
	public function ObjectTestForm() {
		$fields = new FieldList(
			$tagField = new TagField('Tags', null, null, 'TagFieldTest_BlogEntry')
		);
		$actions = new FieldList(
			new FormAction('ObjectTestForm_submit')
		);
		$form = new Form($this, 'ObjectTestForm', $fields, $actions);
		
		return $form;
	}
	
	public function ObjectTestForm_submit($data, $form) {
		$data->saveInto($form);
	}
	
	public function TextbasedTestForm() {
		$fields = new FieldList(
			$tagField = new TagField('TextbasedTags', null, null, 'TagFieldTest_BlogEntry')
		);
		$actions = new FieldList(
			new FormAction('TextbasedTestForm_submit')
		);
		$form = new Form($this, 'TextbasedTestForm', $fields, $actions);
		
		return $form;
	}
	
	public function TextbasedTestForm_submit($data, $form) {
		$data->saveInto($form);
	}
	
}