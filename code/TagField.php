<?php

/**
 * Provides a tagging interface, storing links between tag DataObjects and a parent DataObject.
 *
 * @package forms
 * @subpackage fields
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
	protected $shouldLazyLoad = false;

	/**
	 * @var int
	 */
	protected $lazyLoadItemLimit = 10;

	/**
	 * @var bool
	 */
	protected $canCreate = true;

	/**
	 * @var string
	 */
	protected $titleField = 'Title';

	/**
	 * @var string
	 */
	protected $isMultiple;

	/**
	 * @param string $name
	 * @param string $title
	 * @param null|DataList $source
	 * @param null|DataList $value
	 */
	public function __construct($name, $title = '', $source = null, $value = null) {
		parent::__construct($name, $title, $source, $value);
	}

	/**
	 * @return bool
	 */
	public function getShouldLazyLoad() {
		return $this->shouldLazyLoad;
	}

	/**
	 * @param bool $shouldLazyLoad
	 *
	 * @return static
	 */
	public function setShouldLazyLoad($shouldLazyLoad) {
		$this->shouldLazyLoad = $shouldLazyLoad;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getLazyLoadItemLimit() {
		return $this->lazyLoadItemLimit;
	}

	/**
	 * @param int $lazyLoadItemLimit
	 *
	 * @return static
	 */
	public function setLazyLoadItemLimit($lazyLoadItemLimit) {
		$this->lazyLoadItemLimit = $lazyLoadItemLimit;

		return $this;
	}

    /**
	 * @return bool
	 */
	public function getIsMultiple() {
		return $this->isMultiple;
	}

	/**
	 * @param bool $isMultiple
	 *
	 * @return static
	 */
	public function setIsMultiple($isMultiple) {
		$this->isMultiple = $isMultiple;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function getCanCreate() {
		return $this->canCreate;
	}

	/**
	 * @param bool $canCreate
	 *
	 * @return static
	 */
	public function setCanCreate($canCreate) {
		$this->canCreate = $canCreate;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getTitleField() {
		return $this->titleField;
	}

	/**
	 * @param string $titleField
	 *
	 * @return $this
	 */
	public function setTitleField($titleField) {
		$this->titleField = $titleField;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function Field($properties = array()) {
		Requirements::css(TAG_FIELD_DIR . '/css/select2.min.css');
		Requirements::css(TAG_FIELD_DIR . '/css/TagField.css');

		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-entwine/dist/jquery.entwine-dist.js');
		Requirements::javascript(TAG_FIELD_DIR . '/js/select2.js');
		Requirements::javascript(TAG_FIELD_DIR . '/js/TagField.js');

		$this->addExtraClass('ss-tag-field');

        if ($this->getIsMultiple()) {
		    $this->setAttribute('multiple', 'multiple');
        }

		if($this->shouldLazyLoad) {
			$this->setAttribute('data-ss-tag-field-suggest-url', $this->getSuggestURL());
		} else {
			$properties = array_merge($properties, array(
				'Options' => $this->getOptions()
			));
		}

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

		if(!$source) {
			$source = new ArrayList();
		}

		$dataClass = $source->dataClass();

		$values = $this->Value();

		if(!$values) {
			return $options;
		}

		if(is_array($values)) {
			$values = DataList::create($dataClass)->filter('ID', $values);
		}

		$ids = $values->column('ID');

		$titleField = $this->getTitleField();

		foreach($source as $object) {
			$options->push(
				ArrayData::create(array(
					'Title' => $object->$titleField,
					'Value' => $object->ID,
					'Selected' => in_array($object->ID, $ids),
				))
			);
		}

		return $options;
	}

	/**
	 * {@inheritdoc}
	 */
	public function setValue($value, $source = null) {
		if($source instanceof DataObject) {
			$name = $this->getName();

			if($source->hasMethod($name)) {
				$value = $source->$name()->getIDList();
			}
		} elseif($value instanceof SS_List) {
			$value = $value->column('ID');
		}

		if(!is_array($value)) {
			return parent::setValue($value);
		}

		return parent::setValue(array_filter($value));
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
		$titleField = $this->getTitleField();

		$source = $this->getSource();

		$dataClass = $source->dataClass();

		$values = $this->Value();

		if(!$values) {
			$values = array();
		}

		if(empty($record) || empty($source) || empty($titleField)) {
			return;
		}

		if(!$record->hasMethod($name)) {
			throw new Exception(
				sprintf("%s does not have a %s method", get_class($record), $name)
			);
		}

		$relation = $record->$name();

		foreach($values as $i => $value) {
			if(!is_numeric($value)) {
				if(!$this->getCanCreate()) {
					unset($values[$i]);
					continue;
				}

				$record = new $dataClass();
				$record->{$titleField} = $value;
				$record->write();

				$values[$i] = $record->ID;
			}
		}

		if($values instanceof SS_List) {
			$values = iterator_to_array($values);
		}

		$relation->setByIDList(array_filter($values));
	}

	/**
	 * Returns a JSON string of tags, for lazy loading.
	 *
	 * @param SS_HTTPRequest $request
	 *
	 * @return SS_HTTPResponse
	 */
	public function suggest(SS_HTTPRequest $request) {
		$tags = $this->getTags($request->getVar('term'));

		$response = new SS_HTTPResponse();
		$response->addHeader('Content-Type', 'application/json');
		$response->setBody(json_encode(array('items' => $tags)));

		return $response;
	}

	/**
	 * Returns array of arrays representing tags.
	 *
	 * @param string $term
	 *
	 * @return array
	 */
	protected function getTags($term) {
		/**
		 * @var DataList $source
		 */
		$source = $this->getSource();

		$dataClass = $source->dataClass();

		$titleField = $this->getTitleField();

		$term = Convert::raw2sql($term);

		$query = $dataClass::get()
			->filter($titleField . ':PartialMatch:nocase', $term)
			->sort($titleField)
			->limit($this->getLazyLoadItemLimit());

		$items = array();

		foreach($query->map('ID', $titleField) as $id => $title) {
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
	 * DropdownField assumes value will be a scalar so we must
	 * override validate. This only applies to Silverstripe 3.2+
	 *
	 * @param Validator $validator
	 * @return bool
	 */
	public function validate($validator) {
		return true;
	}

}
