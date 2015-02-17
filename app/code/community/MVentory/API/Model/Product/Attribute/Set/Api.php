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
 */

/**
 * Catalog product attribute set api
 *
 * @package MVentory/API
 */
class MVentory_API_Model_Product_Attribute_Set_Api
  extends Mage_Catalog_Model_Product_Attribute_Set_Api {

  public function fullInfoList () {
    $storeId = Mage::helper('mventory')->getCurrentStoreId(null);

    $attributeApi = Mage::getModel('mventory/product_attribute_api');

    $sets = $this->itemsPerStoreView($storeId);

    foreach ($sets as &$set)
      $set['attributes'] = $attributeApi
                             ->fullInfoList($set['set_id']);

    return $sets;
  }

  private function itemsPerStoreView($storeId)
  {
    $attributeApi = Mage::getModel('mventory/product_attribute_api');
    $attributeModel = Mage::getModel('catalog/resource_eav_attribute')
      ->setEntityTypeId(Mage::getModel('eav/entity')->setType(Mage_Catalog_Model_Product::ENTITY)->getTypeId());

    $entityType = Mage::getModel('catalog/product')->getResource()->getEntityType();
    $collection = Mage::getResourceModel('eav/entity_attribute_set_collection')
      ->setEntityTypeFilter($entityType->getId());

    $result = array();

    foreach ($collection as $attributeSet)
    {
      /* Assume the set needs to be included in the response but try to prove otherwise */
      $isIncluded = true;

      $attributesList = $attributeApi->items($attributeSet->getId());

      /* Search for the formatting attribute */
      foreach ($attributesList as $attribute)
      {
        $attributeModel->load($attribute['attribute_id']);

        $frontendLabel = $attributeModel->getFrontend()->getLabel();
        if (is_array($frontendLabel))
        {
          $frontendLabel = array_shift($frontendLabel);
        }

        if (strcmp($frontendLabel, $attributeSet->getAttributeSetName()) == 0)
        {
          /* We found the formatting attribute. Check if its label for the current storeview equals "~"
           * and exclude the entire attribute set if this is true. */
          $storeLabels = $attributeModel->getStoreLabels();
          $storeLabel = isset($storeLabels[$storeId]) ? $storeLabels[$storeId] : '';
              	
          if (strcmp($storeLabel, "~") == 0)
          {
            $isIncluded = false;

            /* We have just proven this attribute set needs to be excluded. No need to iterate anymore. */
            break;
          }
        }
      }

      if ($isIncluded)
      {
        $result[] = array(
          'set_id' => $attributeSet->getId(),
          'name'   => $attributeSet->getAttributeSetName()
        );
      }
    }

    return $result;
  }
}
