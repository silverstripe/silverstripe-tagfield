<?php
/**
 * Provides a Formfield for saving a string of tags into either
 * a many_many relationship or a text property.
 * By default, tags are separated by whitespace.
 *
 * Features:
 * - Bundled with jQuery-based autocomplete library which is applied to a textfield
 * - Autosuggest functionality (currently JSON only)
 * 
 * Example Datamodel and Instanciation:
 * <code>
 * class Article extends DataObject {
 * 	static $many_many = array('Tags'=>'Tag');
 * }
 * class Tag extends DataObject {
 * 	static $db = array('Title'=>'Varchar');
 * 	static $belongs_many_many = array('Articles'=>'Article');
 * }
 * </code>
 * <code>
 * $form = new Form($this,'Form',
 * 	new FieldSet(
 * 		new TagField('Tags', 'My Tags', null, 'Article')
 *	)
 * 	new FieldSet()
 * );
 * $form->loadDataFrom($myArticle);
 * $form->saveInto($myArticle);
 * </code>
 * 
 * @author Ingo Schommer, SilverStripe Ltd. (<firstname>@silverstripe.com)
 * @package formfields
 * @subpackage tagfield
 */
class TagField extends TextField {
	
	/**
	 * @var string $tagTextbasedClass The DataObject class with a text property
	 * or many_many relation matching the name of the Field.
	 */
	protected $tagTopicClass;
	
	/**
	 * @var string $tagFieldName Only applies to object-based tagging.
	 * The fieldname for textbased tagging is inferred from the formfield name.
	 */
	protected $tagFieldName = 'Title';
	
	/**
	 * @var string $tagFilter
	 */
	protected $tagFilter;
	
	/**
	 * @var string $tagSort If {@link suggest()} finds multiple matches, in which order should they
	 * be presented.
	 */
	protected $tagSort;
	
	/**
	 * @var $separator Determines on which character to split tags in a string.
	 */
	protected $separator = ' ';
	
	protected static $separator_to_regex = array(
		' ' => '\s',
		',' => '\,',
	);
	
	/**
	 * @var array $customTags Override the tagging behaviour with a custom set
	 * which is used by the javascript library directly instead of querying {@link suggest()}.
	 */
	protected $customTags;
	
	/**
	 * @var int $maxSuggestionsNum Maximum number of suggestions for each lookup.
	 * Keep this number low for performance reasons and to avoid UI clutter.
	 */
	public $maxSuggestionsNum = 50;
	
	function __construct($name, $title = null, $value = null, $tagTopicClass = null, $tagFieldName = "Title") {
		$this->tagTopicClass = $tagTopicClass;
		$this->tagFieldName = ($tagFieldName) ? $tagFieldName : $name; 
		
		parent::__construct($name, $title, $value);
	}
	
	public function Field() {
		Requirements::javascript(THIRDPARTY_DIR . "/jquery/jquery.js");
		Requirements::javascript(THIRDPARTY_DIR . "/jquery/jquery_improvements.js");
		Requirements::javascript("tagfield/javascript/jquery.tags.js");
		Requirements::css("tagfield/css/TagField.css");

		// Standard textfield stuff
		$attributes = array(
			'type' => 'text',
			'class' => 'text tagField',
			'id' => $this->id(),
			'name' => $this->Name(),
			'value' => $this->Value(),
			'tabindex' => $this->getTabIndex(),
			'maxlength' => ($this->maxLength) ? $this->maxLength : null,
			'size' => ($this->maxLength) ? min( $this->maxLength, 30 ) : null,
		);
		if($this->disabled) $attributes['disabled'] = 'disabled';
		
		// Data passed as custom attributes
		if($this->customTags) $attributes['tags'] = $this->customTags;
		else $attributes['href'] = parse_url($this->Link(),PHP_URL_PATH);
		$attributes['rel'] = $this->separator;

		return $this->createTag('input', $attributes);
	}
	
	/**
	 * Helper for autocompletion in javascript library.
	 * 
	 * @return string JSON array
	 */
	public function suggest($request) {
		$tagTopicClassObj = singleton($this->getTagTopicClass());
		
		$searchString = $request->requestVar('tag');
		
		if($this->customTags) {
			$tags = $this->customTags;
		} else if($tagTopicClassObj->many_many($this->tagFieldName)) {
			$tags = $this->getObjectTags($searchString);
		} else if($tagTopicClassObj->hasField($this->tagFieldName)) {
			$tags = $this->getTextbasedTags($searchString);
		} else {
			user_error('TagField::suggest(): Cant find valid relation or text property with name "' . $this->tagFieldName . '"', E_USER_ERROR);
		}
		
		return Convert::raw2json($tags);
	}
	
	function saveInto($record) {		
		if($this->value) {
			// $record should match the $tagTopicClass
			if($record->many_many($this->tagFieldName)) {
				$this->saveIntoObjectTags($record);
			} elseif($record->hasField($this->tagFieldName)) {
				$this->saveIntoTextbasedTags($record);
			} else {
				user_error('TagField::saveInto(): Cant find valid field or relation to save into', E_USER_ERROR);
			}
		}
	}
	
	function setValue($value, $obj = null) {
		if(isset($obj) && is_object($obj) && $obj instanceof DataObject && $obj->many_many($this->tagFieldName)) {
			//if(!$obj->many_many($this->Name())) user_error("TagField::setValue(): Cant find relationship named '$this->Name()' on object", E_USER_ERROR);
			$tags = $obj->{$this->tagFieldName}();
			$this->value = implode($this->separator, array_values($tags->map('ID',$this->tagFieldName)));
		} else {
			parent::setValue($value, $obj);
		}
	}
	
	/**
	 * @param string $class Classname of the DataObject which contains
	 * the relation or field for tags.
	 */
	public function setTagTopicClass($class) {
		$this->tagTopicClass = $class;
	}
	
	/**
	 * @return string Classname
	 */
	public function getTagTopicClass() {
		$record = ($this->form) ? $this->form->getRecord() : null;
		if($this->tagTopicClass) {
			return $this->tagTopicClass;
		} elseif($record) {
			return $record->ClassName;
		} else {
			return false;
		}
	}
	
	protected function saveIntoObjectTags($record) {
		// HACK We can't save relationship tables without having an ID
		if(!$record->isInDB()) $record->write();
		
		$tagsArr = $this->splitTagsToArray($this->value);
		$relationName = $this->tagFieldName;
		$existingTagsComponentSet = $record->$relationName();
		$tagClass = $this->getTagClass();
		$tagBaseClass = ClassInfo::baseDataClass($tagClass);
		
		$tagsToAdd = array();
		if($tagsArr) foreach($tagsArr as $tagString) {
			$SQL_filter = sprintf('`%s`.`%s` = "%s"',
				$tagBaseClass,
				$this->tagFieldName,
				Convert::raw2sql($tagString)
			);
			$tagObj = DataObject::get_one($tagClass, $SQL_filter);
			if(!$tagObj) {
				$tagObj = new $tagClass();
				$tagObj->{$this->tagFieldName} = $tagString;
				$tagObj->write();
			}
			$tagsToAdd[] = $tagObj;
		}

		// remove all before readding
		$existingTagsComponentSet->removeAll();
		$existingTagsComponentSet->addMany($tagsToAdd);
	}
	
	protected function saveIntoTextbasedTags($record) {
		$tagFieldName = $this->tagFieldName;
		
		// necessary step to filter whitespace etc.
		$RAW_tagsArr = $this->splitTagsToArray($this->value);
		$record->$tagFieldName = $this->combineTagsFromArray($RAW_tagsArr);
	}
	
	protected function splitTagsToArray($tagsString) {
		$separator = (isset(self::$separator_to_regex[$this->separator])) ? self::$separator_to_regex[$this->separator] : $this->separator;
		return array_unique(preg_split('/\s*' . $separator . '\s*/', trim($tagsString)));
	}
	
	protected function combineTagsFromArray($tagsArr) {
		return ($tagsArr) ? implode($this->separator, $tagsArr) : array();
	}
	
	/**
	 * Use only when storing tags in objects
	 */
	protected function getTagClass() {
		$tagManyMany = singleton($this->getTagTopicClass())->many_many($this->tagFieldName);
		if(!$tagManyMany) {
			user_error('TagField::getTagClass(): Cant find relation with name "' . $this->tagFieldName . '"', E_USER_ERROR);
		}

		return $tagManyMany[1];
	}
	
	protected function getObjectTags($searchString) {
		$tagClass = $this->getTagClass();
		$tagBaseClass = ClassInfo::baseDataClass($tagClass);
		
		$SQL_filter = sprintf("`%s`.`%s` LIKE '%%%s%%'",
			$tagBaseClass,
			$this->tagFieldName,
			Convert::raw2sql($searchString)
		);
		if($this->tagFilter) $SQL_filter .= ' AND ' . $this->tagFilter;
		
		$tagObjs = DataObject::get($tagClass, $SQL_filter, $this->tagSort, "", $this->maxSuggestionsNum);
		$tagArr = ($tagObjs) ? array_values($tagObjs->map('ID', $this->tagFieldName)) : array();
		
		return $tagArr;
	}
	
	/**
	 * Return an array map of unique tags from
	 * the field name of the topic class.
	 * 
	 * @param $searchString The text to search for tags
	 * @return array
	 */
	protected function getTextbasedTags($searchString) {
		$baseClass = ClassInfo::baseDataClass($this->getTagTopicClass());

		$SQL_filter = sprintf("`%s`.`%s` LIKE '%%%s%%'",
			$baseClass,
			$this->tagFieldName,
			Convert::raw2sql($searchString)
		);
		if($this->tagFilter) $SQL_filter .= ' AND ' . $this->tagFilter;
		
		$sql = "SELECT `" . $this->tagFieldName . "` FROM `".$this->getTagTopicClass() . "` WHERE ".$SQL_filter;
		$map = DB::query($sql)->column();
		
		// remove duplicates (retains case sensitive duplicates)
		$filteredMap = array_unique($map);
		
		return $filteredMap;
	}
	
	public function setTagFilter($sql) {
		$this->tagFilter = $sql;
	}
	
	public function getTagFilter() {
		return $this->tagFilter;
	}
	
	public function setTagSort($sql) {
		$this->tagSort = $sql;
	}
	
	public function getTagSort() {
		return $this->tagSort;
	}
	
	public function setSeparator($separator) {
		$this->separator = $separator;
	}
	
	public function getSeparator() {
		return $this->separator;
	}
	
	public function setCustomTags($tags) {
		$this->customTags = $tags;
	}
	
	public function getCustomTags() {
		return $this->customTags;
	}
	
}
?>
