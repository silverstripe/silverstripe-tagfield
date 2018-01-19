<?php

namespace SilverStripe\TagField;

use SilverStripe\Forms\ReadonlyField;

/**
 * A readonly extension of TagField useful for non-editable items within the CMS.
 *
 * @package forms
 * @subpackage fields
 */
class ReadonlyTagField extends TagField
{
    /**
     * {@inheritDoc}
     */
    protected $readonly = true;

    /**
     * Render the readonly field as HTML.
     *
     * @param array $properties
     * @return HTMLText
     */
    public function Field($properties = array())
    {
        $options = array();

        foreach ($this->getOptions()->filter('Selected', true) as $option) {
            $options[] = $option->Title;
        }

        $field = ReadonlyField::create($this->name . '_Readonly', $this->title);

        $field->setForm($this->form);
        $field->setValue(implode(', ', $options));
        return $field->Field();
    }
}
