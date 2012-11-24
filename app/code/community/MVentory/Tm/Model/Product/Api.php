<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Catalog product api
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class MVentory_Tm_Model_Product_Api extends Mage_Catalog_Model_Product_Api {

  const FETCH_LIMIT_PATH = 'mventory_tm/api/products-number-to-fetch';
  const TAX_CLASS_PATH = 'mventory_tm/api/tax_class';

  public function fullInfo ($id = null, $sku = null) {
    $tmDataHelper = Mage::helper('mventory_tm');

    $product = Mage::getModel('catalog/product');

    if (! $id)
      $id = $product->getResource()->getIdBySku($sku);

    $id = (int) $id;

    $website = $tmDataHelper->getWebsite($id);
    $storeId = $website
                 ->getDefaultStore()
                 ->getId();

    $result = $this->info($id, $storeId, null, 'id');
    $product = $product->load($id);

    $stockItem = Mage::getModel('mventory_tm/stock_item_api');

    $_result = $stockItem->items($id);

    if (isset($_result[0]))
      $result = array_merge($result, $_result[0]);

    $productAttribute = Mage::getModel('catalog/product_attribute_api');

    $_result = $productAttribute->items($result['set']);

    $result['set_attributes'] = array();

    foreach ($_result as $_attr) {
      $attr = $productAttribute->info($_attr['attribute_id'], $storeId);

      $attr['options']
        = $productAttribute->options($attr['attribute_id'], $storeId);

      $result['set_attributes'][] = $attr;
    }

    $productAttributeMedia
      = Mage::getModel('catalog/product_attribute_media_api');

    $baseUrlPath = Mage_Core_Model_Store::XML_PATH_UNSECURE_BASE_URL;

    $mediaPath = Mage::getStoreConfig($baseUrlPath, $storeId)
                 . 'media/'
                 . Mage::getSingleton('catalog/product_media_config')
                     ->getBaseMediaUrlAddition();

    $images = $productAttributeMedia->items($id, $storeId, 'id');

    foreach ($images as &$image)
      $image['url'] = $mediaPath . $image['file'];

    $result['images'] = $images;

    $category = Mage::getModel('catalog/category_api');

    foreach ($result['categories'] as $i => $categoryId)
      $result['categories'][$i] = $category->info($categoryId, $storeId);

    //TM specific details start here
    $buyNowPath = MVentory_Tm_Model_Connector::BUY_NOW_PATH;
    $tmFeesPath = MVentory_Tm_Model_Connector::ADD_TM_FEES_PATH;
    $shippingTypePath = MVentory_Tm_Model_Connector::SHIPPING_TYPE_PATH;
    $relistIfNotSoldPath = MVentory_Tm_Model_Connector::RELIST_IF_NOT_SOLD_PATH;

    $listingId = $product->getTmListingId();

    $result['tm_options'] = array();
    $result['tm_options']['allow_buy_now'] = $tmDataHelper->getConfig($buyNowPath, $website);
    $result['tm_options']['add_tm_fees'] = $tmDataHelper->getConfig($tmFeesPath, $website);
    $result['tm_options']['shipping_type'] = $tmDataHelper->getConfig($shippingTypePath, $website);
    $result['tm_options']['relist'] = $tmDataHelper->getConfig($relistIfNotSoldPath, $website);

    if ($listingId) {
      $result['tm_options']['tm_listing_id'] = $listingId;
    }

    $shippingTypes
      = Mage::getModel('mventory_tm/system_config_source_shippingtype')
        ->toOptionArray();

    $result['tm_options']['shipping_types_list'] = $shippingTypes;

    $tmTmHelper = Mage::helper('mventory_tm/tm');
    $result['tm_options']['tm_accounts'] = array();
    foreach ($tmTmHelper->getAccounts($website) as $id => $account) {
      $result['tm_options']['tm_accounts'][$id] = $account['name'];
    }

    if (!count($result['category_ids'])) {
      $result['tm_options']['preselected_categories'] = null;
    } else {
      $mageCategory = Mage::getModel('catalog/category')->load($result['category_ids'][0]);

      $tmAssignedCategoryIds = $mageCategory->getTmAssignedCategories();

      if ($tmAssignedCategoryIds && is_string($tmAssignedCategoryIds)) {
        $tmAssignedCategoryIds = explode(',', $tmAssignedCategoryIds);
        $result['tm_options']['preselected_categories'] = array();
        $tmAllCategories = Mage::getModel('mventory_tm/connector')->getTmCategories();

        foreach ($tmAssignedCategoryIds as $id) {
          if (isset($tmAllCategories[$id])) {
            $result['tm_options']['preselected_categories'][$id] = $tmAllCategories[$id]['path'];
          }
        }
      } else {
        $result['tm_options']['preselected_categories'] = null;
      }
    }

    return $result;
  }

  public function limitedList ($name = null, $categoryId = null, $page = 1) {
    $storeId = Mage::helper('mventory_tm')->getCurrentStoreId();

    $limit = (int) Mage::getStoreConfig(self::FETCH_LIMIT_PATH, $storeId);

    if ($categoryId) {
      $category = Mage::getModel('catalog/category')
                    ->setStoreId($storeId)
                    ->load($categoryId);

      if (!$category->getId())
        $this->_fault('category_not_exists');

      $collection = $category->getProductCollection();
    } else {
      $collection = Mage::getModel('catalog/product')
                      ->getCollection()
                      ->addStoreFilter($storeId);
    }

    if ($name)
      $collection->addAttributeToFilter(
          array(
              array('attribute'=> 'name','like' => "%{$name}%"),
              array('attribute'=> 'sku','like' => "%{$name}%"))
      );

    $collection
      ->addAttributeToSelect('name')
      ->setPage($page, $limit);

    if (!$name)
      $collection
        ->setOrder('updated_at', Varien_Data_Collection::SORT_ORDER_DESC);

    $result = array('items' => array());

    foreach ($collection as $product)
      $result['items'][] = array('product_id' => $product->getId(),
                                 'sku' => $product->getSku(),
                                 'name' => $product->getName(),
                                 'set' => $product->getAttributeSetId(),
                                 'type' => $product->getTypeId(),
                                 'category_ids' => $product->getCategoryIds() );

    $result['current_page'] = $collection->getCurPage();
    $result['last_page'] = (int) $collection->getLastPageNumber();

    return $result;
  }

  public function createAndReturnInfo ($type, $set, $sku, $productData,
                                   $storeId = null) {

    $id = (int) Mage::getModel('catalog/product')
                  ->getResource()
                  ->getIdBySku($sku);

    if (! $id) {
      $helper = Mage::helper('mventory_tm');

      $storeId = $helper->getCurrentStoreId($storeId);

      $productData['website_ids'] = $helper->getWebsitesForProduct($storeId);

      //Set visibility to "Catalog, Search" value
      $productData['visibility'] = 4;

      //if (!isset($productData['tax_class_id']))
        $productData['tax_class_id']
          = (int) $helper->getConfig(self::TAX_CLASS_PATH,
                                     $helper->getCurrentWebsite());

      //Set storeId as null to save values of attributes in the default scope
      $id = $this->create($type, $set, $sku, $productData, null);
    }

    return $this->fullInfo($id);
  }
  
  /**
   * Get info about new products, sales and stock      
   *      
   * @return array     
   */
  public function statistics () {
    $storeId    = Mage::helper('mventory_tm')->getCurrentStoreId();
    $store      = Mage::app()->getStore($storeId);

    $date       = new Zend_Date();
    
    $dayStart   = $date->toString('yyyy-MM-dd 00:00:00');
    
    $weekStart  = new Zend_Date($date->getTimestamp() - 7 * 24 * 3600);
    
    $monthStart = new Zend_Date($date->getTimestamp() - 30 * 24 * 3600);

    // Get Sales info   
    $collection = Mage::getModel('sales/order')->getCollection();
    $collection
      ->getSelect()
      ->columns('SUM(grand_total) as sum');
    $collection
      ->addFieldToFilter('store_id', $storeId)
      ->addFieldToFilter('created_at', array(
        'from' => $dayStart));
    $daySales = trim($collection
                  ->load()
                  ->getFirstItem()
                  ->getData('sum'), 0);
    
    $collection = Mage::getModel('sales/order')->getCollection();
    $collection
      ->getSelect()
      ->columns('SUM(grand_total) as sum');
    $collection
      ->addFieldToFilter('store_id', $storeId)
      ->addFieldToFilter('created_at', array(
        'from' => $weekStart->toString('YYYY-MM-dd 00:00:00')));
    $weekSales = trim($collection
                   ->load()
                   ->getFirstItem()
                   ->getData('sum'), 0);
    
    $collection = Mage::getModel('sales/order')->getCollection();
    $collection
      ->getSelect()
      ->columns('SUM(grand_total) as sum');
    $collection
      ->addFieldToFilter('store_id', $storeId)
      ->addFieldToFilter('created_at', array(
        'from' => $monthStart->toString('YYYY-MM-dd 00:00:00')));
    $monthSales = trim($collection
                    ->load()
                    ->getFirstItem()
                    ->getData('sum'), 0);
    
    $collection = Mage::getModel('sales/order')->getCollection();
    $collection
      ->getSelect()
      ->columns('SUM(grand_total) as sum');
    $collection->addFieldToFilter('store_id', $storeId);
    $totalSales = trim($collection
                    ->load()
                    ->getFirstItem()
                    ->getData('sum'), 0);
    // End of Sales info
    
    // Get Stock info
    $collection = Mage::getModel('catalog/product')->getCollection();

    if (Mage::helper('catalog')->isModuleEnabled('Mage_CatalogInventory')) {
      $collection
        ->joinField('qty', 
                    'cataloginventory/stock_item', 
                    'qty', 'product_id=entity_id', 
                    '{{table}}.stock_id=1 AND {{table}}.is_in_stock=1
                    AND {{table}}.manage_stock=1 AND {{table}}.qty>0', 'left');
    }
    if ($storeId) {
      //$collection->setStoreId($store->getId());
      $collection->addStoreFilter($store);
      
      $collection->joinAttribute(
        'price',
        'catalog_product/price',
        'entity_id',
        null,
        'left',
        $storeId
      );
    } else {
      $collection->addAttributeToSelect('price');
    }
      
    $collection
      ->getSelect()
      ->columns(array('SUM(at_qty.qty) AS total_qty', 
                      'SUM(at_qty.qty*at_price.value) AS total_value'));
    $result = $collection
                ->load()
                ->getFirstItem()
                ->getData();
      
    $totalStockQty = trim($result['total_qty'], 0);
    $totalStockValue = trim($result['total_value'], 0);
    // End of Stock info
    
    // Get Products info       
    $collection = Mage::getModel('catalog/product')->getCollection();
    $collection
      ->getSelect()
      ->columns('COUNT(entity_id) as loaded');
    $collection
      ->addStoreFilter($store)
      ->addFieldToFilter('created_at', array(
        'from' => $dayStart));
    $dayLoaded = $collection
                   ->load()
                   ->getFirstItem()
                   ->getData('loaded');
    
    $collection = Mage::getModel('catalog/product')->getCollection();
    $collection
      ->getSelect()
      ->columns('COUNT(entity_id) as loaded');
    $collection
      ->addStoreFilter($store)
      ->addFieldToFilter('created_at', array(
        'from' => $weekStart->toString('YYYY-MM-dd 00:00:00')));                                                 
    $weekLoaded  = $collection
                     ->load()
                     ->getFirstItem()
                     ->getData('loaded');
    
    $collection = Mage::getModel('catalog/product')->getCollection();
    $collection
      ->getSelect()
      ->columns('COUNT(entity_id) as loaded');
    $collection
      ->addStoreFilter($store)
      ->addFieldToFilter('created_at', array(
        'from' => $monthStart->toString('YYYY-MM-dd 00:00:00')));
    $monthLoaded = $collection
                     ->load()
                     ->getFirstItem()
                     ->getData('loaded');
    // End of Products info

    return array('day_sales' => (double)$daySales,
                 'week_sales' => (double)$weekSales,
                 'month_sales' => (double)$monthSales,
                 'total_sales' => (double)$totalSales,
                 'total_stock_qty' => (double)$totalStockQty,
                 'total_stock_value' => (double)$totalStockValue,
                 'day_loaded' => (double)$dayLoaded,
                 'week_loaded' => (double)$weekLoaded,
                 'month_loaded' => (double)$monthLoaded);
  }

  public function submitToTM ($productId, $tmData) {
    $product = Mage::getModel('catalog/product')->load($productId);

    if (is_null($product->getId())) {
      $this->_fault('product_not_exists');
    }

    $connector = Mage::getModel('mventory_tm/connector');

    $connectorResult = $connector->send($product, $tmData['tm_category_id'], $tmData);

    if (is_int($connectorResult)) {
      $product
        ->setTmListingId($connectorResult)
        ->save();
    }

    $result = $this->fullInfo($productId, null);

    if (!is_int($connectorResult))
    {
      $result['tm_error'] = $connectorResult;
    }

    return $result;
  }
}
