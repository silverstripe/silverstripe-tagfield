# Using

The primary use, for this module, is as a custom input field interface. For instance, imagine you had the following data objects:

```php
class BlogPost extends DataObject {
	private static $many_many = array(
		'BlogTags' => 'BlogTag'
	);
}

class BlogTag extends DataObject {
	private static $db = array(
		'Title' => 'Varchar(200)',
	);

	private static $belongs_many_many = array(
		'BlogPosts' => 'BlogPost'
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

This will present a tag field, in which you can select existing blog tags or create new ones. They will be created/linked after the blog posts are saved.

You can also store string-based tags, for blog posts, with the following field type:

```php
$field = StringTagField::create(
	'Tags',
	'Tags',
	array('one', 'two'),
	explode(',', $this->Tags)
);

$field->setShouldLazyLoad(true); // tags should be lazy loaded
```

This assumes you are storing tags in the following data object structure:

```php
class BlogPost extends DataObject {
	private static $db = array(
		'Tags' => 'Text'
	);
}
```

These tag field classes extend the `DropdownField` class. Their template(s) don't alter the underlying select element structure, so you can interact with them as with any normal select element. You can also interact with the Select2 instance applied to each field, as you would any other time when using Select2.

> Chosen is applied to all select elements in the CMS, so this module attempts to remove it before applying Select2. Review the companion JS files to see how that happens...
