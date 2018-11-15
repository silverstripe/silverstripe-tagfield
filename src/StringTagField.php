<?php

namespace SilverStripe\TagField;

use Iterator;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Convert;
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

    /**
     * {@inheritdoc}
     */
    public function Field($properties = [])
    {
        Requirements::css('silverstripe/tagfield:client/dist/styles/bundle.css');
        Requirements::javascript('silverstripe/tagfield:client/dist/js/bundle.js');

        $this->addExtraClass('ss-tag-field');

        if ($this->getIsMultiple()) {
            $this->setAttribute('multiple', 'multiple');
        }

        if ($this->getShouldLazyLoad()) {
            $this->setAttribute('data-ss-tag-field-suggest-url', $this->getSuggestURL());
        } else {
            $properties = array_merge($properties, [
                'Options' => $this->getOptions()
            ]);
        }

        $this->setAttribute('data-can-create', (int) $this->getCanCreate());

        return $this
            ->customise($properties)
            ->renderWith(TagField::class);
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
                ArrayData::create([
                    'Title' => $value,
                    'Value' => $value,
                    'Selected' => in_array($value, $values),
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

        if ($value === null) {
            $value = [];
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
            ['name' => $this->getName() . '[]']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function saveInto(DataObjectInterface $record)
    {
        parent::saveInto($record);

        $name = $this->getName();

        $record->$name = implode(',', $this->Value());
        $record->write();
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
            ['items' => []]
        );

        $response = HTTPResponse::create();
        $response->addHeader('Content-Type', 'application/json');

        if ($record = $this->getRecord()) {
            $tags = [];
            $term = $request->getVar('term');

            if ($record->hasField($this->getName())) {
                $tags = $this->getTags($term);
            }

            $responseBody = json_encode(
                ['items' => $tags]
            );
        }

        $response->setBody($responseBody);

        return $response;
    }

    /**
     * Returns array of arrays representing tags.
     *
     * @param string $term
     * @return array
     */
    protected function getTags($term)
    {
        $record = $this->getRecord();

        if (!$record) {
            return [];
        }

        $fieldName = $this->getName();
        $className = $record->getClassName();

        $term = Convert::raw2sql($term);

        $query = DataList::create($className)
            ->filter($fieldName . ':PartialMatch:nocase', $term)
            ->limit($this->getLazyLoadItemLimit());

        $items = array();

        foreach ($query->column($fieldName) as $tags) {
            $tags = explode(',', $tags);

            foreach ($tags as $i => $tag) {
                if (stripos($tag, $term) !== false && !in_array($tag, $items)) {
                    $items[] = [
                        'id' => $tag,
                        'text' => $tag,
                    ];
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
     * @return $this
     */
    public function setCanCreate($canCreate)
    {
        $this->canCreate = $canCreate;

        return $this;
    }
}
