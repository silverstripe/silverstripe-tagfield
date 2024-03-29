<?php

namespace SilverStripe\TagField\Tests\Stub;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\TagField\Tests\Stub\TagFieldTestBlogTag;

class TagFieldTestBlogPost extends DataObject implements TestOnly
{
    private static $table_name = 'TagFieldTestBlogPost';

    private static $db = [
        'Title'   => 'Text',
        'Content' => 'Text'
    ];

    private static $many_many = [
        'Tags' => TagFieldTestBlogTag::class
    ];

    private static $has_one = [
        'PrimaryTag' => TagFieldTestBlogTag::class
    ];
}
