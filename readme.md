# Tag Field

Custom tag input field, for SilverStripe.

[![Build Status](https://api.travis-ci.com/silverstripe/silverstripe-tagfield.svg?branch=2)](https://travis-ci.com/silverstripe/silverstripe-tagfield)
[![Code Quality](http://img.shields.io/scrutinizer/g/silverstripe/silverstripe-tagfield.svg?style=flat)](https://scrutinizer-ci.com/g/silverstripe/silverstripe-tagfield)
[![Code coverage](https://codecov.io/gh/silverstripe/silverstripe-tagfield/branch/master/graph/badge.svg)](https://codecov.io/gh/silverstripe/silverstripe-tagfield)
[![Version](http://img.shields.io/packagist/v/silverstripe/tagfield.svg?style=flat)](https://packagist.org/packages/silverstripe/tagfield)
[![License](http://img.shields.io/packagist/l/silverstripe/tagfield.svg?style=flat)](license.md)
[![SilverStripe supported module](https://img.shields.io/badge/silverstripe-supported-0071C4.svg)](https://www.silverstripe.org/software/addons/silverstripe-commercially-supported-module-list/)

## Overview

![Screenshot](docs/en/screenshot.png)

Allows storing tags as a relationship, or comma-delimited strings.
Supports autocompletion with lazy-loading.

Note: The field is optimised for usage in the SilverStripe CMS UI.
The form field class itself can be used outside of the CMS,
but you'll need to build your own frontend to interpret the raw field data (`data-schema` attribute).

## Requirements

* SilverStripe 4.0

## Installing

```sh
$ composer require silverstripe/tagfield
```

## Using

### Relational Tags

```php
use SilverStripe\ORM\DataObject;

class BlogPost extends DataObject
{
	private static $many_many = [
		'BlogTags' => BlogTag::class
	];
}
```

```php
use SilverStripe\ORM\DataObject;

class BlogTag extends DataObject
{
	private static $db = [
		'Title' => 'Varchar(200)',
	];

	private static $belongs_many_many = [
		'BlogPosts' => BlogPost::class
	];
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
**Note:** This assumes you have imported the namespaces class, e.g. use SilverStripe\TagField\TagField;

### String Tags

```php
use SilverStripe\ORM\DataObject;

class BlogPost extends DataObject
{
	private static $db = [
		'Tags' => 'Text',
	];
}
```

```php
$field = StringTagField::create(
	'Tags',
	'Tags',
    ['one', 'two'],
	explode(',', $this->Tags)
);

$field->setShouldLazyLoad(true); // tags should be lazy loaded
```

You can find more in-depth documentation in [docs/en](docs/en/introduction.md).

## Versioning

This library follows [Semver](http://semver.org). According to Semver, you will be able to upgrade to any minor or patch version of this library without any breaking changes to the public API. Semver also requires that we clearly define the public API for this library.

All methods, with `public` visibility, are part of the public API. All other methods are not part of the public API. Where possible, we'll try to keep `protected` methods backwards-compatible in minor/patch versions, but if you're overriding methods then please test your work before upgrading.

## Reporting Issues

Please [create an issue](http://github.com/silverstripe/silverstripe-tagfield/issues) for any bugs you've found, or features you're missing.
