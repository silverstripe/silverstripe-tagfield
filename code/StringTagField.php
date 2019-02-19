<?php

/**
 * Provides a tagging interface, storing comma-delimited tags in a DataObject string field.
 *
 * This is intended bridge the gap between 1.x and 2.x, and when possible TagField should be used
 * instead.
 *
 * @package forms
 * @subpackage fields
 */
class StringTagField extends DropdownField
{
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
     * @var null|DataObject
     */
    protected $record;

    /**
     * @var bool
     */
    protected $isMultiple = true;

    /**
     * @param string $name
     * @param string $title
     * @param array|SS_List $source
     * @param array|SS_List $value
     */
    public function __construct($name, $title = '', $source = array(), $value = array())
    {
        parent::__construct($name, $title, $source, $value);
    }

    /**
     * @return bool
     */
    public function getShouldLazyLoad()
    {
        return $this->shouldLazyLoad;
    }

    /**
     * @param bool $shouldLazyLoad
     *
     * @return static
     */
    public function setShouldLazyLoad($shouldLazyLoad)
    {
        $this->shouldLazyLoad = $shouldLazyLoad;

        return $this;
    }

    /**
     * @return int
     */
    public function getLazyLoadItemLimit()
    {
        return $this->lazyLoadItemLimit;
    }

    /**
     * @param int $lazyLoadItemLimit
     *
     * @return static
     */
    public function setLazyLoadItemLimit($lazyLoadItemLimit)
    {
        $this->lazyLoadItemLimit = $lazyLoadItemLimit;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsMultiple()
    {
        return $this->isMultiple;
    }

    /**
     * @param bool $isMultiple
     *
     * @return static
     */
    public function setIsMultiple($isMultiple)
    {
        $this->isMultiple = $isMultiple;

        return $this;
    }

    /**
     * @return null|DataObject
     */
    public function getRecord()
    {
        if ($this->record) {
            return $this->record;
        }

        if ($form = $this->getForm()) {
            return $form->getRecord();
        }

        return null;
    }

    /**
     * @param DataObject $record
     *
     * @return $this
     */
    public function setRecord(DataObject $record)
    {
        $this->record = $record;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function Field($properties = array())
    {
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

        if ($this->getShouldLazyLoad()) {
            $this->setAttribute('data-ss-tag-field-suggest-url', $this->getSuggestURL());
        } else {
            $properties = array_merge($properties, array(
                'Options' => $this->getOptions()
            ));
        }

        $this->setAttribute('data-can-create', (int) $this->getCanCreate());

        return $this
            ->customise($properties)
            ->renderWith(array("templates/TagField"));
    }

    /**
     * @return string
     */
    protected function getSuggestURL()
    {
        return Controller::join_links($this->Link(), 'suggest');
    }

    /**
     * @return ArrayList
     */
    protected function getOptions()
    {
        $options = ArrayList::create();

        $source = $this->getSource();

        if ($source instanceof Iterator) {
            $source = iterator_to_array($source);
        }

        $values = $this->Value();

        foreach ($source as $value) {
            $options->push(
                ArrayData::create(array(
                    'Title' => $value,
                    'Value' => $value,
                    'Selected' => in_array($value, $values),
                ))
            );
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($value, $source = null)
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if ($source instanceof DataObject) {
            $name = $this->getName();
            $value = explode(',', $source->$name);
        }

        if ($source instanceof SS_List) {
            $value = $source->column('ID');
        }

        if (is_null($value)) {
            $value = array();
        }

        return parent::setValue(array_filter($value));
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes()
    {
        return array_merge(
            parent::getAttributes(),
            array('name' => $this->getName() . '[]')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function saveInto(DataObjectInterface $record)
    {
        parent::saveInto($record);

        $name = $this->getName();

        $record->$name = $this->dataValue();
        $record->write();
    }

    /**
     * Ensure that arrays are imploded before being saved
     *
     * @return mixed|string
     */
    public function dataValue()
    {
        return implode(',', $this->value);
    }

    /**
     * Returns a JSON string of tags, for lazy loading.
     *
     * @param SS_HTTPRequest $request
     *
     * @return SS_HTTPResponse
     */
    public function suggest(SS_HTTPRequest $request)
    {
        $responseBody = Convert::raw2json(
            array('items' => array())
        );

        $response = new SS_HTTPResponse();
        $response->addHeader('Content-Type', 'application/json');

        if ($record = $this->getRecord()) {
            $tags = array();
            $term = $request->getVar('term');

            if ($record->hasField($this->getName())) {
                $tags = $this->getTags($term);
            }

            $responseBody = Convert::raw2json(
                array('items' => $tags)
            );
        }

        $response->setBody($responseBody);

        return $response;
    }

    /**
     * Returns array of arrays representing tags.
     *
     * @param string $term
     *
     * @return array
     */
    protected function getTags($term)
    {
        $record = $this->getRecord();

        if (!$record) {
            return array();
        }

        $fieldName = $this->getName();
        $className = $record->getClassName();

        $term = Convert::raw2sql($term);

        $query = $className::get()
            ->filter($fieldName . ':PartialMatch:nocase', $term)
            ->limit($this->getLazyLoadItemLimit());

        $items = array();

        foreach ($query->column($fieldName) as $tags) {
            $tags = explode(',', $tags);

            foreach ($tags as $i => $tag) {
                if (stripos($tag, $term) !== false && !in_array($tag, $items)) {
                    $items[] = array(
                        'id' => $tag,
                        'text' => $tag
                    );
                }
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
    public function validate($validator)
    {
        return true;
    }

    /**
     * @return bool
     */
    public function getCanCreate()
    {
        return $this->canCreate;
    }

    /**
     * @param bool $canCreate
     *
     * @return static
     */
    public function setCanCreate($canCreate)
    {
        $this->canCreate = $canCreate;

        return $this;
    }
}
