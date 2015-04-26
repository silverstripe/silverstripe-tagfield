<?php

/**
 * Tag field, using jQuery/Select2.
 *
 * @package    forms
 * @subpackage fields-formattedinput
 */
class TagField extends DropdownField {
	/**
	 * @var array
	 */
	public static $allowed_actions = array(
		'suggest',
	);

	/**
	 * @var bool
	 */
	protected $ajax = false;

	/**
	 * @var bool
	 */
	protected $readOnly = false;

	/**
	 * @var string
	 */
	protected $relationTitle = 'Title';

	/**
	 * @var int
	 */
	protected $ajaxItemLimit = 10;

	/**
	 * @var null|string
	 */
	protected $recordClass;

	/**
	 * @param string $name
	 * @param null|string $title
	 * @param array $source
	 * @param array $value
	 * @param bool $readOnly
	 */
	public function __construct($name, $title = null, $source = array(), $value = array(), $readOnly = false) {
		$this->setReadOnly($readOnly);

		parent::__construct($name, $title, $source, $value);
	}

	/**
	 * @return bool
	 */
	public function getAjax() {
		return $this->ajax;
	}

	/**
	 * @param bool $ajax
	 *
	 * @return static
	 */
	public function setAjax($ajax) {
		$this->ajax = $ajax;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function getReadOnly() {
		return $this->readOnly;
	}

	/**
	 * @param bool $readOnly
	 *
	 * @return static
	 */
	public function setReadOnly($readOnly) {
		$this->readOnly = $readOnly;

		return $this;
	}

	/**
	 * @return null|string
	 */
	public function getRelationTitle() {
		return $this->relationTitle;
	}

	/**
	 * @param string $relationTitle
	 *
	 * @return static
	 */
	public function setRelationTitle($relationTitle) {
		$this->relationTitle = $relationTitle;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getAjaxItemLimit() {
		return $this->ajaxItemLimit;
	}

	/**
	 * @param int $ajaxItemLimit
	 *
	 * @return static
	 */
	public function setAjaxItemLimit($ajaxItemLimit) {
		$this->ajaxItemLimit = $ajaxItemLimit;

		return $this;
	}

	/**
	 * @return null|string
	 */
	public function getRecordClass() {
		return $this->recordClass;
	}

	/**
	 * @param string $recordClass
	 *
	 * @return static
	 */
	public function setRecordClass($recordClass) {
		$this->recordClass = $recordClass;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function Field($properties = array()) {
		Requirements::css(TAG_FIELD_DIR . '/css/select2.min.css');
		Requirements::css(TAG_FIELD_DIR . '/css/TagField.css');

		Requirements::javascript(TAG_FIELD_DIR . '/js/TagField.js');
		Requirements::javascript(TAG_FIELD_DIR . '/js/select2.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-entwine/dist/jquery.entwine-dist.js');

		$this->addExtraClass('silverstripe-tag-field');

		$this->setAttribute('multiple', 'multiple');

		if($this->ajax) {
			$this->setAttribute('data-suggest-url', $this->getSuggestURL());
		}

		$properties = array_merge($properties, array(
			'Options' => $this->getOptions()
		));

		return $this
			->customise($properties)
			->renderWith(array("templates/TagField"));
	}

	/**
	 * @return string
	 */
	protected function getSuggestURL() {
		return Controller::join_links($this->Link(), 'suggest');
	}

	/**
	 * @return ArrayList
	 */
	protected function getOptions() {
		$options = ArrayList::create();

		$source = $this->getSource();

		if($source instanceof Iterator) {
			$source = iterator_to_array($source);
		}

		$values = $this->Value();

		foreach($source as $key => $value) {
			$options->push(
				ArrayData::create(array(
					"Title" => $value,
					"Value" => $key,
					"Selected" => in_array($key, $values),
				))
			);
		}

		return $options;
	}

	/**
	 * {@inheritdoc}
	 */
	public function setValue($value, $record = null) {
		if(empty($value) && $record) {
			if($record instanceof DataObject) {
				$name = $this->getName();

				if($record->hasMethod($name)) {
					$value = $record->$name()->getIDList();
				}
			} elseif($record instanceof SS_List) {
				$value = $record->column('ID');
			}
		}

		return parent::setValue($value, $record);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getAttributes() {
		return array_merge(
			parent::getAttributes(),
			array('name' => $this->getName() . '[]')
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function saveInto(DataObjectInterface $record) {
		parent::saveInto($record);

		$name = $this->getName();
		$relationTitle = $this->getRelationTitle();

		$values = $this->Value();

		if(empty($values) || empty($record) || empty($relationTitle)) {
			return;
		}

		if($record->hasMethod($name)) {
			$relation = $record->$name();

			$class = $relation->dataClass();

			foreach($values as $i => $value) {
				if(!is_numeric($value)) {
					if($this->getReadOnly()) {
						unset($values[$i]);
						continue;
					} else {
						$instance = new $class();
						$instance->{$relationTitle} = $value;
						$instance->write();

						$values[$i] = $instance->ID;
					}
				}
			}

			$relation->setByIDList($values);
		} else {
			$record->$name = implode(',', $values);
		}
	}

	/**
	 * Returns a JSON string of tags, for ajax-based search.
	 *
	 * @param SS_HTTPRequest $request
	 *
	 * @return SS_HTTPResponse
	 */
	public function suggest(SS_HTTPRequest $request) {
		$recordClass = $this->getRecordClass();

		$response = new SS_HTTPResponse();

		$response->addHeader('Content-Type', 'application/json');

		$response->setBody(Convert::raw2json(
			array('items' => array())
		));

		if($recordClass !== null) {
			$name = $this->getName();

			/**
			 * @var DataObject $object
			 */
			$object = singleton($recordClass);

			$term = $request->getVar('term');

			$tags = array();

			if($object->hasMethod($name)) {
				$tags = $this->getObjectTags($object, $term);
			} elseif($object->hasField($name)) {
				$tags = $this->getStringTags($term);
			}

			$response->setBody(Convert::raw2json(
				array('items' => $tags)
			));
		}

		return $response;
	}

	/**
	 * Returns array of arrays representing DataObject-based tags.
	 *
	 * @param DataObject $instance
	 * @param string $term
	 *
	 * @return array
	 */
	protected function getObjectTags(DataObject $instance, $term) {
		$name = $this->getName();
		$relationTitle = $this->getRelationTitle();

		$relation = $instance->{$name}();

		$term = Convert::raw2sql($term);

		$query = DataList::create($relation->dataClass())
			->filter($relationTitle . ':PartialMatch:nocase', $term)
			->sort($relationTitle)
			->limit($this->getAjaxItemLimit());

		$items = array();

		foreach($query->map('ID', $relationTitle) as $id => $title) {
			if(!in_array($title, $items)) {
				$items[] = array(
					'id' => $id,
					'text' => $title
				);
			}
		}

		return $items;
	}

	/**
	 * Returns array of arrays representing string-based tags.
	 *
	 * @param string $term
	 *
	 * @return array
	 */
	protected function getStringTags($term) {
		$name = $this->getName();
		$recordClass = $this->getRecordClass();

		$term = Convert::raw2sql($term);

		$query = DataObject::get($recordClass)
			->filter($name . ':PartialMatch:nocase', $term)
			->limit($this->getAjaxItemLimit());

		$items = array();

		foreach($query->column($name) as $tags) {
			$tags = explode(',', $tags);

			foreach($tags as $i => $tag) {
				if(stripos($tag, $term) !== false && !in_array($tag, $items)) {
					$items[] = array(
						'id' => $tag,
						'text' => $tag
					);
				}
			}
		}

		return $items;
	}
}
