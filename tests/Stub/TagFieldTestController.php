<?php

namespace SilverStripe\TagField\Tests\Stub;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\TagField\TagField;

class TagFieldTestController extends Controller implements TestOnly
{
    public function TagFieldTestForm()
    {
        $fields = new FieldList(
            $tagField = new TagField('Tags', '', new DataList('TagFieldTestBlogTag'))
        );

        $actions = new FieldList(
            new FormAction('TagFieldTestFormSubmit')
        );

        return new Form($this, 'TagFieldTestForm', $fields, $actions);
    }

    public function TagFieldTestFormSubmit(DataObject $dataObject, Form $form)
    {
        $form->saveInto($dataObject);
    }
}
