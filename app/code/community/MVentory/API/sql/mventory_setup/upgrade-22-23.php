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

$this->startSetup();

$name = 'product_barcode_';
$entityTypeId = $this->getEntityTypeId('catalog_product');

$this->addAttribute(
  $entityTypeId,
  $name,
  array(
    //Fields from Mage_Eav_Model_Entity_Setup
    'type' => 'varchar',
    'label' => 'Barcode',
    'required' => false,
    'user_defined' => true,

    //Fields from Mage_Catalog_Model_Resource_Setup
    'visible_on_front' => true,
    'is_html_allowed_on_front' => true,
    'is_configurable' => false
  )
);

$setId = $this->getDefaultAttributeSetId($entityTypeId);
$groupId = $this->getDefaultAttributeGroupId($entityTypeId, $setId);

$this->addAttributeToGroup($entityTypeId, $setId, $groupId, $name);

$this->endSetup();
