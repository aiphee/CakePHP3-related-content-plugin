[![Build Status](https://travis-ci.org/aiphee/cakephp-related-content.svg?branch=master)](https://travis-ci.org/aiphee/cakephp-related-content)

# Related content plugin for CakePHP 3

![Autocomplete](/related1.png "Autocomplete")
![Related view](/related2.png "Related view")


This is a fast made plugin made for simple maintaining of relationships.

You can define that one entry in table has similar entries in other tables.

It also has basic element with ajax which relies on `Bootstrap 3`.

Please, be noted that this is not a complex plugin, there may be serious bugs, you are welcome to report or repair them.

## Installation

 - Run migration from plugin or create table manually.
```SQL
CREATE TABLE `related_contents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source_table_name` varchar(255) DEFAULT NULL COMMENT 'Table which you link from',
  `target_table_name` varchar(255) DEFAULT NULL COMMENT 'Table which you link to',
  `source_table_id` int(11) NOT NULL COMMENT 'ID of table you link from',
  `target_table_id` int(11) NOT NULL COMMENT 'ID of table you link to',
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
)
```
 - Copy repo content to plugins directory and add to your cakephp composer.json
```Composer
    "autoload": {
        "psr-4": {
            "App\\": "src",
            "RelatedContent\\": "./plugins/RelatedContent/src",
        }
    }
```
or use packagist `composer require aiphee/related-content`

 - Add to your `bootstrap.php`
```PHP
Plugin::load('RelatedContent', ['routes' => true]);
```

## Usage
 - add first behavior to your table for it to be searchable (added to cache for search)
```PHP
$this->addBehavior('RelatedContent.InRelatedIndex');
```
 - add second behavior which will have Similar
```PHP
$this->addBehavior('RelatedContent.HasRelated', isset($config['options']) ? $config['options'] : []);
```

 - Add element to your view
```PHP
<?= $this->element('RelatedContent.managingRelated', ['tables_to_get' => ['ContentNews', 'ContentPages']]) ?>
```
Parameter `tables_to_get` is optional, it will allow to search just in some tables.

 - To get related content with your entry, you have to pass parameter to find
```PHP
$entity = $this->ContentPages->get($id, [
				'getRelated' => true
			]);
```

- You can also use element in view, which model does not have HasRelated behavior, to populate some fields
(target_table_name and target_table_id are directly in table)
```PHP
<?= $this->element('RelatedContent.foreignTableSearch') ?>
<?= $this->Form->input('foreign_table_id', ['type' => 'hidden']) ?>
<?= $this->Form->input('foreign_table_name', ['type' => 'hidden']) ?>
```
It looks like this then:

![Managing related without associations](/related3.png "Managing related without associations")


