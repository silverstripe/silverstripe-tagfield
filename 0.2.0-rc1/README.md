# TagField Module

## Maintainer Contact

 * Ingo Schommer (Nickname: ischommer) <ingo (at) silverstripe (dot) com>

## Requirements

 * SilverStripe 2.3 or newer
 * Database: MySQL 5+, SQLite3, Postgres 8.3, SQL Server 2008

## Download/Information

 * http://silverstripe.org/tag-field-module

## Introduction

Provides a Formfield for saving a string of tags into either a many_many relationship or a text property. By default, tags are separated by whitespace. Check out a [http://remysharp.com/wp-content/uploads/2007/12/tagging.php](demo of the javascript interface).

## Features

  * Bundled with jQuery-based autocomplete library ([http://remysharp.com/2007/12/28/jquery-tag-suggestion/](website)) which is applied to a textfield
  * Autosuggest functionality (currently JSON only)
  * Saving in many_many relation, or in textfield
  * Static list of tags without hitting the server
  * Tab-autocompletion of tags
  * Customizeable tag layout through css
  * Unobtrusive - still saves with Javascript disabled
  * Full unit test coverage

## Usage

### Tags as Objects

Article Model

	class Article extends DataObject {
		static $many_many = array(
			'RelationTags' => 'Tag'
		);
	}

Tag Model

	class Tag extends DataObject {
		static $db = array(
			'Title' => 'Varchar(200)',
		);

		static $belongs_many_many = array(
			'Articles' => 'Article'
		);
	}

Formfield Instanciation:

	$tagField = new TagField('RelationTags', null, null, 'Article')

### Tags as Textfields

Article Model

	class Article extends DataObject {
		static $db = array(
			'TextTags' => 'Text'
		);
	}

Formfield Instanciation:

	$tagField = new TagField('TextTags', null, null, 'Article')

### Static Tags without Autosuggestion

	$tagField = new TagField('TextTags', null, null, 'Article');
	$tagField->setCustomTags(array('mytag','myothertag'));