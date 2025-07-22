---
title: TagField Module Usage
summary: Managing tags on DataObjects, and integration with silverstripe-taxonomy
icon: tags
---

# Usage

The primary use, for this module, is as a custom input field interface. For instance, imagine you had the following data objects:

```php
namespace App\Models;

use SilverStripe\Blog\Model\BlogTag;
use SilverStripe\ORM\DataObject;

class BlogPost extends DataObject
{
    private static $many_many = [
        'BlogTags' => BlogTag::class,
    ];
}
```

```php
namespace App\Models;

use SilverStripe\Blog\Model\BlogPost;
use SilverStripe\ORM\DataObject;

class BlogTag extends DataObject
{
    private static $db = [
        'Title' => 'Varchar(200)',
    ];

    private static $belongs_many_many = [
        'BlogPosts' => BlogPost::class,
    ];
}
```

If you wanted to link blog tags to blog posts, you might override [`DataObject::getCMSFields()`](api:SilverStripe\ORM\DataObject::getCMSFields()) with the following field:

```php
use SilverStripe\Blog\Model\BlogTag;
use SilverStripe\TagField\TagField;

$field = TagField::create(
    'BlogTags',
    'Blog Tags',
    BlogTag::get(),
    $this->BlogTags()
)
    // tags should be lazy loaded
    ->setShouldLazyLoad(true)
    // new tag DataObjects can be created
    ->setCanCreate(true);
```

This will present a tag field, in which you can select existing blog tags or create new ones. They will be created/linked after the blog posts are saved.

## StringTagField

You can also store string-based tags, with the following field type:

```php
use SilverStripe\TagField\StringTagField;

$field = StringTagField::create(
    'Tags',
    'Tags',
    ['one', 'two'],
    explode(',', $this->Tags)
)
    ->setCanCreate(true)
    ->setShouldLazyLoad(true);
```

This assumes you are storing tags in the following data object structure:

```php
namespace App\Models;

use SilverStripe\ORM\DataObject;

class BlogPost extends DataObject
{
    private static $db = [
        'Tags' => 'Text',
    ];
}
```

In the above code example, the options available (whether lazy loaded or not) would be "one" and "two", and the
user would be able to create new options. Whichever tags are chosen would be stored in the BlogPost's `Tags` field
as a comma-delimited string.

## Using tagfield with silverstripe-taxonomy

TagField assumes that objects have a `Title` field. For classes without a `Title` field
use [`TagField::setTitleField()`](api:SilverStripe\TagField\TagField::setTitleField()) to modify accordingly.

```php
use SilverStripe\TagField\TagField;
use SilverStripe\Taxonomy\TaxonomyTerm;

$field = TagField::create(
    'Tags',
    'Blog Tags',
    TaxonomyTerm::get()
)
    ->setTitleField('Name');
```
