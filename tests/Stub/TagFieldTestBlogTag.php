<?php

namespace SilverStripe\TagField\Tests\Stub;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\TagField\Tests\Stub\TagFieldTestBlogPost;

class TagFieldTestBlogTag extends DataObject implements TestOnly
{
    private static $table_name = 'TagFieldTestBlogTag';

    private static $default_sort = '"TagFieldTestBlogTag"."ID" ASC';

    private static $db = [
        'Title' => 'Varchar(200)'
    ];

    private static $belongs_many_many = [
        'BlogPosts' => TagFieldTestBlogPost::class
    ];
}
