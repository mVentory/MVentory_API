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
 * @copyright Copyright (c) 2014-2016 mVentory Ltd. (http://mventory.com)
 * @license http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

/**
 * Catalog inventory api
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Model_Stock_Item_Api
  extends Mage_CatalogInventory_Model_Stock_Item_Api {

  public function items ($productIds) {
    if (!is_array($productIds))
      $productIds = array($productIds);

    $storeId = Mage::helper('mventory')->getCurrentStoreId();

    $collection = Mage::getModel('catalog/product')
                    ->getCollection()
                    ->setFlag('require_stock_items', true)
                    ->addStoreFilter($storeId)
                    ->addFieldToFilter('entity_id', array('in' => $productIds));

    $result = array();

    foreach ($collection as $product)
      if ($product->getStockItem())
        $result[] = array(
          'product_id' => $product->getId(),
          'sku' => $product->getSku(),
          'qty' => $product->getStockItem()->getQty(),
          'is_in_stock' => $product->getStockItem()->getIsInStock(),
          'manage_stock' => $product->getStockItem()->getManageStock(),
          'is_qty_decimal' => $product->getStockItem()->getIsQtyDecimal()
        );

    return $result;
  }
}
