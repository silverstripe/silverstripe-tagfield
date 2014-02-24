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
 * 		new TagField('Tags', 'My Tags', null, 'Article', 'Title')
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
	 * @var array
	 */
	private static $allowed_actions = array(
		'suggest'
	);
	
	/**
	 * @var string $tagTopicClass The DataObject class with a text property
	 * or many_many relation matching the name of the Field.
	 */
	protected $tagTopicClass;
	
	/**
	 * @var string $tagObjectField Only applies to object-based tagging.
	 * The fieldname for textbased tagging is inferred from the formfield name.
	 * The value should be a property name on the DataObject defined in {@link $tagTopicClass}
	 */
	protected $tagObjectField = 'Title';
	
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
	 * @var array $customTags 
	 */
	protected $customTags;
	
	/**
	 * @var int $maxSuggestionsNum Maximum number of suggestions for each lookup.
	 * Keep this number low for performance reasons and to avoid UI clutter.
	 */
	public $maxSuggestionsNum = 50;
	
	/**
	 * @var boolean Delete tags which are no longer in use.
	 * This only applies for tags defined by a $many_many relation,
	 * text-based tags will be removed implicitly through modifying the actual text value.
	 */
	public $deleteUnusedTags = true;
	
	/**
	 * @var boolean Create new tags if not already defined in the $tagTopicClass table.
	 * This only applies for tags defined by a $many_many relation,
	 * text-based tags will be removed implicitly through modifying the actual text value.
	 */
	public $createNewTags = true;
	
	/*
	 * If no sort clause is provided, then we'll sort it by the ID by default
	 */
	public static $default_sort = "\"ID\" ASC";
	
	function __construct($name, $title = null, $value = null, $tagTopicClass = null, $tagObjectField = "Title") {
		$this->tagTopicClass = $tagTopicClass;
		$this->tagObjectField = $tagObjectField;
		
		parent::__construct($name, $title, $value);
	}
	
	public function Field($properties = array()) {
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-entwine/dist/jquery.entwine-dist.js');

		Requirements::javascript("tagfield/thirdparty/jquery-tags/jquery.tags.js");
		Requirements::javascript("tagfield/javascript/TagField.js");
		Requirements::css("tagfield/css/TagField.css");

		return parent::Field($properties);
	}

	public function getAttributes() {
		$attrs = parent::getAttributes();
		$custom = array(
			'autocomplete' => 'off',
			'class' => $attrs['class'] . ' tagField text',
			// Data passed as custom attributes
			'tags' => $this->customTags ? Convert::raw2json($this->customTags) : null,
			'href' => $this->customTags ? null : parse_url($this->Link(),PHP_URL_PATH) . '/suggest',
			'rel' => $this->separator,
		);

		return array_merge($attrs, $custom);
	}
	
	/**
	 * Helper for autocompletion in javascript library.
	 * 
	 * @return string JSON array
	 */
	public function suggest($request) {
		$tagTopicClassObj = singleton($this->getTagTopicClass());
		
		$searchString = $request->requestVar('tag');
		$searchString = trim($searchString); # incase its comma seperated, and they do comma-space (, ) 
		
		if($this->customTags) {
			$tags = $this->customTags;
		} else if($tagTopicClassObj->many_many($this->getName())) {
			$tags = $this->getObjectTags($searchString);
		} else if($tagTopicClassObj->hasField($this->getName())) {
			$tags = $this->getTextbasedTags($searchString);
		} else {
			user_error('TagField::suggest(): Cant find valid relation or text property with name "' . $this->getName() . '"', E_USER_ERROR);
		}
		
		return Convert::raw2json($tags);
	}
	
	function saveInto(DataObjectInterface $record) {		
		// $record should match the $tagTopicClass
		if($record->many_many($this->getName())) {
			$this->saveIntoObjectTags($record);
		} elseif($record->hasField($this->getName())) {
			$this->saveIntoTextbasedTags($record);
		} else {
			user_error('TagField::saveInto(): Cant find valid field or relation to save into', E_USER_ERROR);
		}
	}
	
	function setValue($value, $obj = null) {
		if(isset($obj) && is_object($obj) && $obj instanceof DataObject && $obj->many_many($this->getName())) {
			//if(!$obj->many_many($this->getName())) user_error("TagField::setValue(): Cant find relationship named '$this->getName()' on object", E_USER_ERROR);
			$tags = $obj->{$this->getName()}();
			$this->value = implode($this->separator, array_values($tags->map('ID',$this->tagObjectField)->toArray()));
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
		$relationName = $this->getName();
		$tagsComponentSet = $record->$relationName();
		$existingTags = $tagsComponentSet->getIdList();
		$tagClass = $this->getTagClass();
		$tagBaseClass = ClassInfo::baseDataClass($tagClass);
		
		// list of new tag IDs
		$newTags = array();
		if($tagsArr) foreach($tagsArr as $tagString) {
			$SQL_filter = sprintf("\"%s\".\"%s\" = '%s'",
				$tagBaseClass,
				$this->tagObjectField,
				Convert::raw2sql($tagString)
			);
			$tagObj = DataObject::get_one($tagClass, $SQL_filter);
			if(!$tagObj && $this->createNewTags) {
				$tagObj = new $tagClass();
				$tagObj->{$this->tagObjectField} = $tagString;
				$tagObj->write();
			}
			if ($tagObj) $newTags[] = $tagObj->ID;
		}
		
		// set new tags
		$tagsComponentSet->setByIdList($newTags);
		
		if($this->deleteUnusedTags) {
			// check for tags which are no longer used
			$removedTags = array_diff($existingTags, $newTags);
			list($parentClass, $tagClass, $parentIDField, $tagIDField, $relationTable) = $record->many_many($relationName);
			$counts = array();
			foreach($removedTags as $removedTagID) {
				$removedTagQuery = new SQLQuery(
					array("COUNT(\"ID\")"),
					array('"'.$relationTable.'"'),
					array(sprintf("\"%s\" = %d", $tagIDField, (int)$removedTagID))
				);
				$removedTagCount = $removedTagQuery->execute()->value();
				
				if($removedTagCount == 0) DataObject::delete_by_id($tagClass, $removedTagID);
			}
		}
	}
	
	protected function saveIntoTextbasedTags($record) {
		$tagFieldName = $this->getName();
		
		// necessary step to filter whitespace etc.
		$RAW_tagsArr = $this->splitTagsToArray($this->value);
		$record->$tagFieldName = $this->combineTagsFromArray($RAW_tagsArr);
	}
	
	protected function splitTagsToArray($tagsString) {
		$separator = (isset(self::$separator_to_regex[$this->separator])) ? self::$separator_to_regex[$this->separator] : $this->separator;
		return array_unique(preg_split('/\s*' . $separator . '\s*/', trim($tagsString), -1, PREG_SPLIT_NO_EMPTY));
	}
	
	protected function combineTagsFromArray($tagsArr) {
		return ($tagsArr) ? implode($this->separator, $tagsArr) : array();
	}
	
	/**
	 * Use only when storing tags in objects
	 */
	protected function getTagClass() {
		$tagManyMany = singleton($this->getTagTopicClass())->many_many($this->getName());
		if(!$tagManyMany) {
			user_error('TagField::getTagClass(): Cant find relation with name "' . $this->getName() . '"', E_USER_ERROR);
		}

		return $tagManyMany[1];
	}
	
	protected function getObjectTags($searchString) {
		$tagClass = $this->getTagClass();
		$tagBaseClass = ClassInfo::baseDataClass($tagClass);
		
		//NOTE: using the LOWER function will mean that indexes will not work on this column.
		$SQL_filter = 'LOWER(' . sprintf("\"%s\".\"%s\") LIKE LOWER('%%%s%%')",
			$tagBaseClass,
			$this->tagObjectField,
			Convert::raw2sql($searchString)
		);
		if($this->tagFilter) $SQL_filter .= ' AND ' . $this->tagFilter;
		
		$tagObjs = DataObject::get($tagClass, $SQL_filter, $this->tagSort, "", $this->maxSuggestionsNum);
		$tagArr = ($tagObjs) ? array_values($tagObjs->map('ID', $this->tagObjectField)->toArray()) : array();
		
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
		//NOTE: using the LOWER function will mean that indexes will not work on this column.
		$filter = 'LOWER(' . sprintf("\"%s\".\"%s\") LIKE LOWER('%%%s%%')",
			$this->getTagTopicClass(),
			$this->getName(),
			Convert::raw2sql($searchString)
		);

		$q = new DataQuery($this->getTagTopicClass());
		$q->where($filter)->sort($this->tagSort);
		if($this->tagFilter) $q->where($this->tagFilter);
		$multipleTagsArr = $q->column($this->getName());

		$filteredTagArr = array();
		if($multipleTagsArr) foreach($multipleTagsArr as $multipleTags) {
			$singleTagsArr = $this->splitTagsToArray($multipleTags);
			foreach($singleTagsArr as $singleTag) {
				// only add those tags of the whole string which
				// match the search terms
				if(stripos($singleTag, $searchString) !== false) {
					$filteredTagArr[] = $singleTag;
				}
			}
		}
		// remove duplicates (retains case sensitive duplicates)
		$filteredTagArr = array_unique($filteredTagArr);
		
		return $filteredTagArr;
	}
	
	public function setTagFilter($sql) {
		$this->tagFilter = $sql;
	}
	
	public function getTagFilter() {
		return $this->tagFilter;
	}
	
	/**
	 * Sort tags (only applicable for many-many relations).
	 * Only works for {@link suggest()}, not for the retrieval
	 * of tag object relations through a {@link ComponentSet}.
	 * 
	 * @param String $sql SQL ORDER BY statement
	 */
	public function setTagSort($sql) {
		$this->tagSort = $sql;
	}
	
	/**
	 * @return String SQL ORDER BY statement
	 */
	public function getTagSort() {
		return $this->tagSort;
	}
	
	/**
	 * Separator between tags (Default: " ").
	 * Will populate through to the JavaScript library as well.
	 * 
	 * @param String $separator
	 */
	public function setSeparator($separator) {
		$this->separator = $separator;
	}
	
	/**
	 * @return String
	 */
	public function getSeparator() {
		return $this->separator;
	}
	
	/**
	 * Override the tagging behaviour with a custom set
	 * which is used by the javascript library directly instead of querying {@link suggest()}.
	 * 
	 * @param array $tags
	 */
	public function setCustomTags($tags) {
		$this->customTags = $tags;
	}
	
	/**
	 * @return array
	 */
	public function getCustomTags() {
		return $this->customTags;
	}	
}
