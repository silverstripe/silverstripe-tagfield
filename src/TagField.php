<?php

namespace SilverStripe\TagField;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\Validator;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\SS_List;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;

/**
 * Provides a tagging interface, storing links between tag DataObjects and a parent DataObject.
 *
 * @package forms
 * @subpackage fields
 */
class TagField extends DropdownField
{
    /**
     * @var array
     */
    private static $allowed_actions = [
        'suggest',
    ];

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
     * @var DataList
     */
    protected $sourceList;

    /**
     * @var bool
     */
    protected $isMultiple = true;

    /**
     * @param string $name
     * @param string $title
     * @param null|DataList $source
     * @param null|DataList $value
     * @param string $titleField
     */
    public function __construct($name, $title = '', $source = [], $value = null, $titleField = 'Title')
    {
        $this->setSourceList($source);
        $this->setTitleField($titleField);
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

    /**
     * @return string
     */
    public function getTitleField()
    {
        return $this->titleField;
    }

    /**
     * @param string $titleField
     *
     * @return $this
     */
    public function setTitleField($titleField)
    {
        $this->titleField = $titleField;

        return $this;
    }

    /**
     * Get the DataList source. The 4.x upgrade for SelectField::setSource starts to convert this to an array
     * @return DataList
     */
    public function getSourceList()
    {
        return $this->sourceList;
    }

    /**
     * Set the model class name for tags
     * @param  DataList $className
     * @return self
     */
    public function setSourceList($sourceList)
    {
        $this->sourceList = $sourceList;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function Field($properties = [])
    {
        $this->addExtraClass('ss-tag-field');

        return $this->customise($properties)->renderWith(self::class);
    }

    /**
     * Provide TagField data to the JSON schema for the frontend component
     *
     * @return array
     */
    public function getSchemaDataDefaults()
    {
        $schema = array_merge(
            parent::getSchemaDataDefaults(),
            [
                'name' => $this->getName() . '[]',
                'lazyLoad' => $this->getShouldLazyLoad(),
                'creatable' => $this->getCanCreate(),
                'multi' => $this->getIsMultiple(),
                'value' => $this->Value(),
                'disabled' => $this->isDisabled() || $this->isReadonly(),
            ]
        );

        if (!$this->getShouldLazyLoad()) {
            $schema['options'] = array_values($this->getOptions()->toNestedArray());
        } else {
            if ($this->Value()) {
                $schema['value'] = $this->getOptions(true)->toNestedArray();
            }
            $schema['optionUrl'] = $this->getSuggestURL();
        }

        return $schema;
    }

    /**
     * When not used in a React form factory context, this adds the schema data to SilverStripe template
     * rendered attributes lists
     *
     * @return array
     */
    public function getAttributes()
    {
        $attributes = parent::getAttributes();
        $attributes['data-schema'] = json_encode($this->getSchemaData());
        return $attributes;
    }

    /**
     * @return string
     */
    protected function getSuggestURL()
    {
        return Controller::join_links($this->Link(), 'suggest');
    }

    /**
     * @param bool $onlySelected Only return options that are selected
     * @return ArrayList
     */
    protected function getOptions($onlySelected = false)
    {
        $source = $this->getSourceList();

        if (!$source) {
            $source = ArrayList::create();
        }

        $dataClass = $source->dataClass();
        $titleField = $this->getTitleField();
        $values = $this->Value();

        if ($values) {
            if (is_array($values)) {
                $values = DataList::create($dataClass)->filter($titleField, $values);
            }
        }
        if ($onlySelected) {
            $source = $values;
        }

        return $source instanceof DataList ? $this->formatOptions($source) : ArrayList::create();
    }

    /**
     * @param DataList $source
     * @return ArrayList
     */
    protected function formatOptions(DataList $source)
    {
        $options = ArrayList::create();
        $titleField = $this->getTitleField();

        foreach ($source as $object) {
            $options->push(
                ArrayData::create([
                    'Title' => $object->$titleField,
                    'Value' => $object->Title,
                ])
            );
        }

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($value, $source = null)
    {
        if ($source instanceof DataObject) {
            $name = $this->getName();

            if ($source->hasMethod($name)) {
                $value = $source->$name()->column($this->getTitleField());
            }
        } elseif ($value instanceof SS_List) {
            $value = $value->column($this->getTitleField());
        }

        if (!is_array($value)) {
            return parent::setValue($value);
        }

        return parent::setValue(array_filter($value));
    }

    /**
     * {@inheritdoc}
     */
    public function saveInto(DataObjectInterface $record)
    {
        parent::saveInto($record);

        $name = $this->getName();
        $titleField = $this->getTitleField();
        $values = $this->Value();
        $relation = $record->$name();
        $ids = [];

        if (!$values) {
            $values = [];
        }
        if (empty($record) || empty($titleField)) {
            return;
        }

        if (!$record->hasMethod($name)) {
            throw new Exception(
                sprintf("%s does not have a %s method", get_class($record), $name)
            );
        }

        foreach ($values as $key => $value) {
            // Get or create record
            $record = $this->getOrCreateTag($value);
            if ($record) {
                $ids[] = $record->ID;
                $values[$key] = $record->Title;
            }
        }

        $relation->setByIDList(array_filter($ids));
    }

    /**
     * Get or create tag with the given value
     *
     * @param  string $term
     * @return DataObject|bool
     */
    protected function getOrCreateTag($term)
    {
        // Check if existing record can be found
        $source = $this->getSourceList();
        $titleField = $this->getTitleField();
        $record = $source
            ->filter($titleField, $term)
            ->first();
        if ($record) {
            return $record;
        }

        // Create new instance if not yet saved
        if ($this->getCanCreate()) {
            $dataClass = $source->dataClass();
            $record = Injector::inst()->create($dataClass);
            $record->{$titleField} = $term;
            $record->write();
            return $record;
        }

        return false;
    }

    /**
     * Returns a JSON string of tags, for lazy loading.
     *
     * @param  HTTPRequest $request
     * @return HTTPResponse
     */
    public function suggest(HTTPRequest $request)
    {
        $tags = $this->getTags($request->getVar('term'));

        $response = HTTPResponse::create();
        $response->addHeader('Content-Type', 'application/json');
        $response->setBody(json_encode(['items' => $tags]));

        return $response;
    }

    /**
     * Returns array of arrays representing tags.
     *
     * @param  string $term
     * @return array
     */
    protected function getTags($term)
    {
        $source = $this->getSourceList();

        $titleField = $this->getTitleField();

        $query = $source
            ->filter($titleField . ':PartialMatch:nocase', $term)
            ->sort($titleField)
            ->limit($this->getLazyLoadItemLimit());

        // Map into a distinct list
        $items = [];
        $titleField = $this->getTitleField();
        foreach ($query->map('ID', $titleField) as $id => $title) {
            $items[$title] = [
                'id' => $title,
                'text' => $title,
            ];
        }

        return array_values($items);
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
     * Converts the field to a readonly variant.
     *
     * @return ReadonlyTagField
     */
    public function performReadonlyTransformation()
    {
        /** @var ReadonlyTagField $copy */
        $copy = $this->castedCopy(ReadonlyTagField::class);
        $copy->setSourceList($this->getSourceList());
        return $copy;
    }

    /**
     * Prevent the default, which would return "tag"
     *
     * @return string
     */
    public function Type()
    {
        return '';
    }
}
