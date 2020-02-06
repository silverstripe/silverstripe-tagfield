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
     * Render the readonly field as HTML.
     *
     * @param array $properties
     * @return HTMLText
     */
    public function Field($properties = array())
    {
        $value_array = $this->value;
        $source = $this->getSource();
        $source = ($source instanceof Map) ? $source->toArray() : $source;
        $return = [];

        foreach ($value_array as $key => $value) {
            if (in_array($value, $source)) {
                $return[] = $value;
            }
        }

        $field = ReadonlyField::create($this->name . '_Readonly', $this->title);
        $field->setForm($this->form);
        $field->setValue(implode(', ', $return));

        return $field->Field();
    }
}
