# TagField Module

[![Build Status](http://img.shields.io/travis/silverstripe-labs/silverstripe-tagfield.svg?style=flat-square)](https://travis-ci.org/silverstripe-labs/silverstripe-tagfield)
[![Code Quality](http://img.shields.io/scrutinizer/g/silverstripe-labs/silverstripe-tagfield.svg?style=flat-square)](https://scrutinizer-ci.com/g/silverstripe-labs/silverstripe-tagfield)

## Requirements

* SilverStripe 3.1 or newer
* Database: MySQL 5+, SQLite3, Postgres 8.3, SQL Server 2008

## Download/Information

* http://silverstripe.org/tag-field-module

## Usage

### Relational Tags w/ Extension

```php
class Tag extends DataObject {
    static $db = array(
        'Title' => 'Varchar(200)',
    );

    static $belongs_many_many = array(
    	'BlogPosts' => 'BlogPost'
    );
}
```

```yml
BlogPost:
  extensions:
    - TaggableExtension
```
### Relational Tags

```php
class BlogPost extends DataObject {
	static $many_many = array(
		'BlogTags' => 'BlogTag'
	);
}
```

```php
class BlogTag extends DataObject {
	static $db = array(
		'Title' => 'Varchar(200)',
	);

	static $belongs_many_many = array(
		'BlogPosts' => 'BlogPost'
	);
}
```

```php
$field = TagField::create(
	'BlogTags',
	'Blog Tags',
	BlogTag::get(),
	$post->BlogTags()
)
	->setShouldLazyLoad(true) // tags should be lazy loaded
	->setCanCreate(true);      // new tag DataObjects can be created
```

### String Tags

```php
class BlogPost extends DataObject {
	static $db = array(
		'Tags' => 'Text'
	);
}
```

```php
$field = StringTagField::create(
	'Tags', 'Tags', array('one', 'two'), explode(',', $this->Tags)
);

$field->setShouldLazyLoad(true); // tags should be lazy loaded
```
