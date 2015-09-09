# Tag Field

Custom tag input field, for SilverStripe.

[![Build Status](http://img.shields.io/travis/silverstripe-labs/silverstripe-tagfield.svg?style=flat-square)](https://travis-ci.org/silverstripe-labs/silverstripe-tagfield)
[![Code Quality](http://img.shields.io/scrutinizer/g/silverstripe-labs/silverstripe-tagfield.svg?style=flat-square)](https://scrutinizer-ci.com/g/silverstripe-labs/silverstripe-tagfield)
[![Code Coverage](http://img.shields.io/scrutinizer/coverage/g/silverstripe-labs/silverstripe-tagfield.svg?style=flat-square)](https://scrutinizer-ci.com/g/silverstripe-labs/silverstripe-tagfield)
[![Version](http://img.shields.io/packagist/v/silverstripe/tagfield.svg?style=flat-square)](https://packagist.org/packages/silverstripe/tagfield)
[![License](http://img.shields.io/packagist/l/silverstripe/tagfield.svg?style=flat-square)](license.md)

## Requirements

* SilverStripe 3.1 or newer
* Database: MySQL 5+, SQLite3, Postgres 8.3, SQL Server 2008

## Installing

```sh
$ composer require silverstripe/tagfield
```

## Using

### Relational Tags

```php
class BlogPost extends DataObject {
	private static $many_many = array(
		'BlogTags' => 'BlogTag'
	);
}
```

```php
class BlogTag extends DataObject {
	private static $db = array(
		'Title' => 'Varchar(200)',
	);

	private static $belongs_many_many = array(
		'BlogPosts' => 'BlogPost'
	);
}
```

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

### String Tags

```php
class BlogPost extends DataObject {
	private static $db = array(
		'Tags' => 'Text'
	);
}
```

```php
$field = StringTagField::create(
	'Tags',
	'Tags',
	array('one', 'two'),
	explode(',', $this->Tags)
);

$field->setShouldLazyLoad(true); // tags should be lazy loaded
```

You can find more in-depth documentation in [docs/en](docs/en/introduction.md).

## Versioning

This library follows [Semver](http://semver.org). According to Semver, you will be able to upgrade to any minor or patch version of this library without any breaking changes to the public API. Semver also requires that we clearly define the public API for this library.

All methods, with `public` visibility, are part of the public API. All other methods are not part of the public API. Where possible, we'll try to keep `protected` methods backwards-compatible in minor/patch versions, but if you're overriding methods then please test your work before upgrading.

## Reporting Issues

Please [create an issue](http://github.com/silverstripe-labs/silverstripe-tagfield/issues) for any bugs you've found, or features you're missing.
