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
 * @copyright Copyright (c) 2014 mVentory Ltd. (http://mventory.com)
 * @license http://creativecommons.org/licenses/by-nc-nd/4.0/
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */

$data = array(
  'name' => array('use_for_search' => 1),
  'weight' => array(
    'input_method' => MVentory_API_Model_Config::MT_INPUT_NUMKBD,
    'alt_input_method' => MVentory_API_Model_Config::MT_INPUT_NUMKBD
  ),
  'sku' => array(
    'input_method' => MVentory_API_Model_Config::MT_INPUT_SCANNER,
    'alt_input_method' => MVentory_API_Model_Config::MT_INPUT_KBD
  ),
  'product_barcode_' => array(
    'input_method' => MVentory_API_Model_Config::MT_INPUT_SCANNER,
    'alt_input_method' => MVentory_API_Model_Config::MT_INPUT_KBD
  )
);

$this->startSetup();

$attrs = Mage::getResourceModel('eav/entity_attribute_collection')
  ->setEntityTypeFilter(
      Mage::getModel('eav/entity_type')->loadByCode(
        Mage_Catalog_Model_Product::ENTITY
      )
    )
  ->setCodeFilter(array('name', 'weight', 'sku', 'product_barcode_'));

foreach ($attrs as $attr)
  $attr
    ->setData('mventory_metadata' , serialize($data[$attr->getAttributeCode()]))
    ->save();

$this->endSetup();
