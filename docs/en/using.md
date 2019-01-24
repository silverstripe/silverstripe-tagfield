# Using

The primary use, for this module, is as a custom input field interface. For instance, imagine you had the following data objects:

```php
class BlogPost extends DataObject
{
	private static $many_many = array(
		'BlogTags' => 'SilverStripe\\Blog\\Model\\BlogTag'
	);
}

class BlogTag extends DataObject
{
	private static $db = array(
		'Title' => 'Varchar(200)'
	);

	private static $belongs_many_many = array(
		'BlogPosts' => 'SilverStripe\\Blog\\Model\\BlogPost'
	);
}
```

If you wanted to link blog tags to blog posts, you might override `getCMSFields` with the following field:

```php
$field = TagField::create(
	'BlogTags',
	'Blog Tags',
	BlogTag::get(),
	$this->BlogTags()
)
	->setShouldLazyLoad(true) // tags should be lazy loaded
	->setCanCreate(true);     // new tag DataObjects can be created
```

**Note:** This assumes you have imported the namespaces class, e.g. `use SilverStripe\TagField\TagField;`.

This will present a tag field, in which you can select existing blog tags or create new ones. They will be created/linked after the blog posts are saved.

### StringTagField

You can also store string-based tags, with the following field type:

```php
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
