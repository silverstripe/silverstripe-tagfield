<?php

namespace SilverStripe\TagField\Tests\Stub;

use SilverStripe\Control\Controller;
use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\TagField\StringTagField;

class StringTagFieldTestController extends Controller implements TestOnly
{
    public function StringTagFieldTestForm()
    {
        $fields = new FieldList(
            $tagField = new StringTagField('Tags')
        );

        $actions = new FieldList(
            new FormAction('StringTagFieldTestFormSubmit')
        );

        return new Form($this, 'StringTagFieldTestForm', $fields, $actions);
    }

    public function StringTagFieldTestFormSubmit(DataObject $dataObject, Form $form)
    {
        $form->saveInto($dataObject);
    }
}
