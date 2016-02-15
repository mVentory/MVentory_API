<?php

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License BY-NC-ND.
 * By Attribution (BY) - You can share this file unchanged, including
 * this copyright statement.
 * Non-Commercial (NC) - You can use this file for non-commercial activities.
 * A commercial license can be purchased separately from mventory.com.
 * No Derivatives (ND) - You can make changes to this file for your own use,
 * but you cannot share or redistribute the changes.  
 *
 * See the full license at http://creativecommons.org/licenses/by-nc-nd/4.0/
 *
 * @package MVentory/API
 * @copyright Copyright (c) 2016 mVentory Ltd. (http://mventory.com)
 * @license http://creativecommons.org/licenses/by-nc-nd/4.0/
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */

//Make previously whitelisted system attributes (we ignored system attributes)
//visible by default for the app

$data = [
  'category_ids' => ['is_visible' => true],
  'description' => ['is_visible' => true],
  'name' => ['is_visible' => true],
  'price' => ['is_visible' => true],
  'short_description' => ['is_visible' => true],
  'sku' => ['is_visible' => true],
  'special_from_date' => ['is_visible' => true],
  'special_to_data' => ['is_visible' => true],
  'special_price' => ['is_visible' => true],
  'tax_class_id' => ['is_visible' => true],
  'weight' => ['is_visible' => true]
];

//Make previously hidden in any website attrubutes as invisible in the app

$metadata = Mage::helper('mventory/metadata');
$attrs = Mage::getResourceModel('catalog/product_attribute_collection');

foreach ($attrs as $attr) try {
  $invisibleForWebsites = $metadata->get($attr, 'invisible_for_websites');

  $isHiddenInAnyWebsite = count($invisibleForWebsites) > 1
                          || (isset($invisibleForWebsites[0])
                              && $invisibleForWebsites[0] !== '');

  if (!$isHiddenInAnyWebsite)
    continue;

  $data[$attr->getAttributeCode()] = ['is_visible' => false];
}
catch (Exception $e) {
  continue;
}

$this
  ->startSetup()
  ->updateMetadata($data)
  ->endSetup();
