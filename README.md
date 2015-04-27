# TagField Module

[![Build Status](https://secure.travis-ci.org/silverstripe-labs/silverstripe-tagfield.png?branch=master)](https://travis-ci.org/silverstripe-labs/silverstripe-tagfield)

## Maintainer Contact

* Christopher Pitt (Nickname: assertchris) <chris (at) silverstripe (dot) com>

## Requirements

* SilverStripe 3.1 or newer
* Database: MySQL 5+, SQLite3, Postgres 8.3, SQL Server 2008

## Download/Information

* http://silverstripe.org/tag-field-module

## Usage

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
$all    = BlogTags::get()->map();
$linked = $post->BlogTags()->map();

$field = new TagField(
	'BlogTags', 'Blog Tags', $all, $linked
);
```