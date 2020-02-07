<?php

namespace SilverStripe\TagField;

use SilverStripe\ORM\Map;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\SingleLookupField;

/**
 * A readonly extension of StringTagField useful for non-editable items within the CMS.
 *
 * @package forms
 * @subpackage fields
 */
class ReadonlyStringTagField extends SingleLookupField
{
    /**
     * Generate a string to load into thie readonly field's value
     *
     * @return string
     */
    public function getFieldValue()
    {
        if (!is_array($this->value)) {
            $value_array = [$this->value];
        } else {
            $value_array = $this->value;
        }

        $source = $this->getSource();
        $source = ($source instanceof Map) ? $source->toArray() : $source;
        $return = [];

        foreach ($value_array as $value) {
            if (in_array($value, $source)) {
                $return[] = $value;
            }
        }

        return implode(', ', $return);
    }

    /**
     * Render the readonly field as HTML.
     *
     * @param array $properties
     * @return HTMLText
     */
    public function Field($properties = array())
    {
        $field = ReadonlyField::create($this->name . '_Readonly', $this->title);
        $field->setForm($this->form);
        $field->setValue($this->getFieldValue());

        return $field->Field();
    }
}
