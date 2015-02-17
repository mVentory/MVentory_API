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
 * Catalog category api
 *
 * @package MVentory/API
 */
class MVentory_API_Model_Category_Api extends Mage_Catalog_Model_Category_Api
{

  private function removeInactive(&$tree)
  {
    foreach($tree['children'] as $idx => &$child)
    {
      if (isset($child['is_active']) && $child['is_active'] == 1) {
        $this->removeInactive($child);
      } else {
        unset($tree['children'][$idx]);
      }
    }
    $tree['children'] = array_values($tree['children']);
  }

  public function treeActiveOnly()
  {
    $helper = Mage::helper('mventory');
    $storeId = $helper->getCurrentStoreId(null);

    $model = Mage::getModel("catalog/category_api");
    $tree = $model->tree(null, $storeId);

    $this->removeInactive($tree);

    return $helper->prepareApiResponse($tree);
  }

}
