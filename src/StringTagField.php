<?php

namespace SilverStripe\TagField;

use Iterator;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\Validator;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\SS_List;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;

/**
 * Provides a tagging interface, storing comma-delimited tags in a DataObject string field.
 *
 * This is intended bridge the gap between 1.x and 2.x, and when possible TagField should be used
 * instead.
 *
 * @package    tagfield
 * @subpackage fields
 */
class StringTagField extends DropdownField
{
    /**
     * @var array
     */
    private static $allowed_actions = [
        'suggest',
    ];

    /**
     * @var bool Triggers a write call within the saveInto function if enabled
     *
     * @deprecated 3.0.0
     */
    private static $immediate_write_enabled = true;

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

    /** @skipUpgrade */
    protected $schemaComponent = 'TagField';

    /**
     * @return bool
     */
    public function getShouldLazyLoad()
    {
        return $this->shouldLazyLoad;
    }

    /**
     * @param bool $shouldLazyLoad
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function setRecord(DataObject $record)
    {
        $this->record = $record;

        return $this;
    }

    public function Field($properties = [])
    {
        $this->addExtraClass('ss-tag-field entwine');

        return $this
            ->customise($properties)
            ->renderWith(TagField::class);
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
                'value' => $this->formatOptions($this->Value()),
                'disabled' => $this->isDisabled() || $this->isReadonly(),
            ]
        );

        if (!$this->getShouldLazyLoad()) {
            $schema['options'] = $this->getOptions()->toNestedArray();
        } else {
            $schema['optionUrl'] = $this->getSuggestURL();
        }

        return $schema;
    }

    protected function formatOptions($fieldValue)
    {
        if (empty($fieldValue)) {
            return [];
        }

        $formattedValue = [];
        foreach ($fieldValue as $value) {
            $formattedValue[] = [
                'Title' => $value,
                'Value' => $value,
            ];
        }
        return $formattedValue;
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
     * @return ArrayList
     */
    protected function getOptions()
    {
        $options = ArrayList::create();

        $source = $this->getSource();

        if ($source instanceof Iterator) {
            $source = iterator_to_array($source);
        }

        foreach ($source as $value) {
            $options->push(
                ArrayData::create([
                    'Title' => $value,
                    'Value' => $value,
                ])
            );
        }

        return $options;
    }

    public function setValue($value, $source = null)
    {
        if (is_string($value)) {
            $value = explode(',', $value ?? '');
        }

        if ($source instanceof DataObject) {
            $name = $this->getName();
            $value = explode(',', $source->$name ?? '');
        }

        if ($source instanceof SS_List) {
            $value = $source->column('ID');
        }

        if ($value === null) {
            $value = [];
        }

        return parent::setValue(array_filter($value ?? []));
    }

    public function saveInto(DataObjectInterface $record)
    {
        parent::saveInto($record);

        $name = $this->getName();

        $record->$name = $this->dataValue();

        if (self::config()->get('immediate_write_enabled')) {
            $record->write();
        }
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
     * @param  HTTPRequest $request
     * @return HTTPResponse
     */
    public function suggest(HTTPRequest $request)
    {
        $responseBody = json_encode(
            ['items' => $this->getTags($request->getVar('term'))]
        );

        $response = HTTPResponse::create();
        $response->addHeader('Content-Type', 'application/json');
        $response->setBody($responseBody);

        return $response;
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
        if (!$source) {
            return false;
        }

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

            if (is_array($term)) {
                $term = $term['Value'];
            }

            $record->{$titleField} = $term;
            $record->write();
            if ($source instanceof SS_List) {
                $source->add($record);
            }
            return $record;
        }

        return false;
    }


    /**
     * Returns array of arrays representing tags that partially match the given search term
     *
     * @param string $term
     * @return array
     */
    protected function getTags($term)
    {
        $items = [];
        foreach ($this->getOptions() as $i => $tag) {
            /** @var ArrayData $tag */
            $tagValue = $tag->Value;
            // Map into a distinct list (prevent duplicates)
            if (stripos($tagValue ?? '', $term ?? '') !== false && !array_key_exists($tagValue, $items ?? [])) {
                $items[$tagValue] = [
                    'id' => $tag->Title,
                    'text' => $tag->Value,
                ];
            }
        }
        // @todo do we actually need lazy loading limits for StringTagField?
        return array_slice(array_values($items ?? []), 0, $this->getLazyLoadItemLimit());
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
     * @return $this
     */
    public function setCanCreate($canCreate)
    {
        $this->canCreate = $canCreate;

        return $this;
    }
}
