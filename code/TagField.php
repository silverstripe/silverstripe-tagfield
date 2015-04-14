<?php

/**
 * Tag field, using jQuery/Select2.
 *
 * @package    forms
 * @subpackage fields-formattedinput
 */
class TagField extends DropdownField {
	/**
	 * @var bool
	 */
	protected $readOnly;

	/**
	 * @var null|string
	 */
	protected $relationTitleField;

	/**
	 * @param string      $name
	 * @param null|string $title
	 * @param array       $source
	 * @param array       $value
	 * @param bool        $readOnly
	 * @param string      $relationTitleField
	 */
	public function __construct($name, $title = null, $source = array(), $value = array(), $readOnly = false, $relationTitleField = 'Title') {
		$this->readOnly = $readOnly;
		$this->relationTitleField = $relationTitleField;

		parent::__construct($name, $title, $source, $value);
	}

	/**
	 * @param array $properties
	 *
	 * @return string
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

		$options = ArrayList::create();

		$values = $this->Value();

		foreach(iterator_to_array($this->source) as $key => $value) {
			$options->push(
				ArrayData::create(array(
					"Title" => $value,
					"Value" => $key,
					"Selected" => in_array($key, $values),
				))
			);
		}

		$properties = array_merge($properties, array(
			'Options' => $options
		));

		return $this
			->customise($properties)
			->renderWith(array("templates/TagField"));
	}

	/**
	 * Loads the related record values into this field. TagField can be uploaded
	 * in one of three ways:
	 *
	 *  - By passing in a list of object IDs in the $value parameter (an array with a single
	 *    key 'Files', with the value being the actual array of IDs).
	 *  - By passing in an explicit list of File objects in the $record parameter, and
	 *    leaving $value blank.
	 *  - By passing in a dataobject in the $record parameter, from which file objects
	 *    will be extracting using the field name as the relation field.
	 *
	 * Each of these methods will update both the items (list of File objects) and the
	 * field value (list of file ID values).
	 *
	 * @param array                    $value  Array of submitted form data, if submitting from a
	 *                                         form
	 * @param array|DataObject|SS_List $record Full source record, either as a DataObject,
	 *                                         SS_List of items, or an array of submitted form data
	 *
	 * @return UploadField Self reference
	 */
	public function setValue($value, $record = null) {
		// If we're not passed a value directly, we can attempt to infer the field
		// value from the second parameter by inspecting its relations

		// Determine format of presented data
		if(empty($value) && $record) {
			// If a record is given as a second parameter, but no submitted values,
			// then we should inspect this instead for the form values

			if(($record instanceof DataObject)
				&& $record->hasMethod($this->getName())
			) {
				$value = $record
					->{$this->getName()}()
					->getIDList();
			} elseif($record instanceof SS_List) {
				// If directly passing a list then save the items directly
				$value = $record->column('ID');
			}
		}

		return parent::setValue($value, $record);
	}

	/**
	 * @return array
	 */
	public function getAttributes() {
		return array_merge(
			parent::getAttributes(),
			array('name' => $this->getName() . '[]')
		);
	}

	/**
	 * Save the current value of this TagField into a DataObject.
	 * If the field it is saving to is a has_many or many_many relationship,
	 * it is saved by setByIDList(), otherwise it creates a comma separated
	 * list for a standard DB text/varchar field.
	 *
	 * @param DataObjectInterface $record
	 */
	public function saveInto(DataObjectInterface $record) {
		parent::saveInto($record);

		$name = $this->name;

		$values = $this->Value();

		if(empty($values) || empty($record) || empty($this->relationTitleField)) {
			return;
		}

		if($record->hasMethod($name)) {
			$relation = $record->$name();

			$class = $relation->dataClass();

			foreach($values as $i => $value) {
				if(!is_numeric($value)) {
					if($this->readOnly) {
						unset($values[$i]);
						continue;
					} else {
						$instance = new $class();
						$instance->{$this->relationTitleField} = $value;
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
}
