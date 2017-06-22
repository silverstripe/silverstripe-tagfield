<?php

namespace SilverStripe\TagField\Tests\Stub;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class StringTagFieldTestBlogPost extends DataObject implements TestOnly
{
    private static $table_name = 'StringTagFieldTestBlogPost';

    private static $db = [
        'Title' => 'Text',
        'Content' => 'Text',
        'Tags' => 'Text',
    ];
}
