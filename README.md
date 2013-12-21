DataObject-as-Page-Filter
=========================

## Maintainers

 * Aram Balakjian
  <aram at carboncrayon dot com>

## Modules Required

  arambalakjian/dataobjectaspage

## Branch Requirements

 * 3.1 -> SilverStripe 3.1.x

## Overview ##
Filtering System for DOAP Module. Allows you to create multiple category types attached to your DOAP in a many_many or has_many relationship.

Very simply configuration allows you to have a multi select filter (complete with linkingmode to allow styling of selected/unselected) or a select one filter. You can also choose between match any and match all.

[Example 1](http://www.mymuswell.com/places/restaurants-in-muswell-hill/) 

**Restaurant Type** is a select one filter
**Facilities** is a multi select filter with Match All enabled

[Example 2](http://www.mymuswell.com/articles/)

**Category** is a multi select filter with Match Any enabled

# Basic usage example

Your Listing page class:

**FilteredProductListingPage.php**
```php

class FilteredProductListingPage extends FilteredListingPage{
}

class FilteredProductListingPage_Controller extends FilteredListingPage_Controller
{
	//Layout template to use to when AJAX is enabled (ignore if AJAX disabled)
	private static $ajax_template = 'FilteredProductListingPage';

    //This needs to know be the Class of the DataObject you want this page to list
    private static $item_class = 'Product';
    
    //Set the sort for the items (defaults to Created DESC)
    private static $item_sort = 'Created DESC';
    
    //Disable AJAX filtering and reload the page when filtering (defaults to true)
    private static $ajax_filter = false;
	
	private static $filter_settings = array(
		'Categories' => array(
			'Title' => 'Choose Category',	//Define the Title of the Filter (Defailts to Fieldname)
			'Preposition' => 'in', 			//Define the preposition in the filter message, e.g. Products IN x or y category (Defaults to "in")
			'MultiSelect' => false, 		//Select Multiple options at once (default is true)
			'MatchAll' => false  			//Match all the multi selected items, i.e. select a Product which has category x AND y. Requires a Many_Many or Has_Many
		)
	);
}

```

Your DataObjectAsPage class:

**Product.php**
```php
<?php

class Product extends DataObjectAsPage 
{
    //The class of the page which will list this DataObject
    static $listing_class = 'ProductListingPage';
    //Class Naming (optional but reccomended)
    static $plural_name = 'Products';
    static $singular_name = 'Product';
	
	static $many_many = array(
		'Categories' => 'ProductCategory'
	);	
}

```


Your Category class (add as many as you like):

**ProductCategory.php**
```php

class ProductCategory extends DataObjectAsPageCategory 
{
	private static $listing_page_class = 'FilteredProductListingPage';
	private static $singular_name = 'Category';
	private static $plural_name = 'Categories';
	
	private static $belongs_many_many = array(
		'Product' => 'Product' 	
	);
}
```