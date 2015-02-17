<?php

$version = '1.4.0';

$notes = <<<EOT
* Added use of SKU attribute when searching product by barcode

* Support for multicategories in category matching editor

* Category matching editor improvements (better help and error messages)

* Dropped functionality for sorting attributes in catalog filter, functionality for better product details on frontend

* Other small improvements and bug fixes
EOT;

$description = <<<EOT
mVentory is an Android application for efficient management of Magento stores. This extension provides additional API functionality and is required for the app to connect to your Magento website.



App Features



 Create new products

 Edit product details and attributes

 Take product photos with the phone

 Take photos with an external camera and upload via the phone

 Upload photos from a digital library or from the Internet

 Scan product barcodes

 Check out product sold in the shop

 View and manage orders

 Manage shipping, scan shipping labels



Download the app from Google Play (https://play.google.com/) or find out more on http://mventory.com



Configuration



You need to configure user access and product attributes. Read a step by step guide (http://www.mventory.com/installation) or contact our support on info@mventory.com



License



Creative Commons BY-NC-ND 4.0 (http://creativecommons.org/licenses/by-nc-nd/4.0/)



The extension is free for non-commercial or trial use. Please, request a FREE commercial license from info@mventory.com

Visit http://mventory.com for more info.
EOT;

$summary = <<<EOT
The easiest way to sell online: PoS, Inventory and Website control in one elegant Android application.
EOT;

return array(

//The Magento Connect extension name.  Must be unique on Magento Connect
//Has no relation to your code module name.  Will be the Connect extension name
'extension_name'         => 'MVentory_API',

//Your extension version.  By default, if you're creating an extension from a 
//single Magento module, the tar-to-connect script will look to make sure this
//matches the module version.  You can skip this check by setting the 
//skip_version_compare value to true
'extension_version'      => $version,
'skip_version_compare'   => true,

//You can also have the package script use the version in the module you 
//are packaging with. 
'auto_detect_version'   => false,

//Magento Connect license value. 
'stability'              => 'stable',

//Magento Connect license value 
'license'                => 'CC BY-NC-ND 4.0',

//Magento Connect license URI 
'license_uri'            => 'http://creativecommons.org/licenses/by-nc-nd/4.0/',

//Magento Connect channel value.  This should almost always (always?) be community
'channel'                => 'community',

//Magento Connect information fields.
'summary'                => $summary,
'description'            => $description,
'notes'                  => $notes,

//Magento Connect author information. If author_email is foo@example.com, script will
//prompt you for the correct name.  Should match your http://www.magentocommerce.com/
//login email address
'author_name'            => 'Anatoly A. Kazantsev',
'author_user'            => 'anatoly',
'author_email'           => 'anatoly@mventory.com',
/*
// Optional: adds additional author nodes to package.xml
'additional_authors'     => array(
  array(
    'author_name'        => 'Author 2',
    'author_user'        => 'author2',
    'author_email'       => 'foo2@example.com',
  ),
  array(
    'author_name'        => 'Author 3',
    'author_user'        => 'author3',
    'author_email'       => 'foo3@example.com',
  ),
),
*/
//PHP min/max fields for Connect.  I don't know if anyone uses these, but you should
//probably check that they're accurate
'php_min'                => '5.3.0',
'php_max'                => '6.0.0',

//PHP extension dependencies. An array containing one or more of either:
//  - a single string (the name of the extension dependency); use this if the
//    extension version does not matter
//  - an associative array with 'name', 'min', and 'max' keys which correspond
//    to the extension's name and min/max required versions
//Example:
//    array('json', array('name' => 'mongo', 'min' => '1.3.0', 'max' => '1.4.0'))
'extensions'             => array(),
'packages'               => array()
);
