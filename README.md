# TagField Module

[![Build Status](http://img.shields.io/travis/silverstripe-labs/silverstripe-tagfield.svg?style=flat-square)](https://travis-ci.org/silverstripe-labs/silverstripe-tagfield)
[![Code Quality](http://img.shields.io/scrutinizer/g/silverstripe-labs/silverstripe-tagfield.svg?style=flat-square)](https://scrutinizer-ci.com/g/silverstripe-labs/silverstripe-tagfield)

## Requirements

* SilverStripe 3.1 or newer
* Database: MySQL 5+, SQLite3, Postgres 8.3, SQL Server 2008

## Download/Information

* http://silverstripe.org/tag-field-module

## Usage

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
$field = new TagField(
	'BlogTags', 'Blog Tags', BlogTags::get(), $post->BlogTags()
);

$field->setShouldLazyLoad(true); // tags should be lazy loaded
$field->setCanCreate(true);      // new tag DataObjects can be created
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
$field = new StringTagField(
	'BlogTags', 'Blog Tags', array('one', 'two'), explode(',', $post->Tags)
);

$field->setShouldLazyLoad(true); // tags should be lazy loaded
```
