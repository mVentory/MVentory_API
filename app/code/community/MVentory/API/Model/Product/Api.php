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
 * Catalog product api
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Model_Product_Api extends Mage_Catalog_Model_Product_Api {

  const __INVALID_VAL = 'Value for "%s" is invalid';
  const __INVALID_VAL_ERR = 'Value for "%s" is invalid: %s';

  const CONF_TYPE = Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE;

  protected $_allowUpdate = array(
    //!!!TODO: remove it after the app will treat status as normal attribute
    //         Add to whitelist in MVentory_API_Helper_Product_Attribute
    'status' => true,

    'stock_data' => true
  );

  public function fullInfo ($productId,
                            $identifierType = null,
                            $none = false) {

    //Support for not updated apps which requests product's info
    //by SKU or Barcode.
    //
    // * 1st param is null
    // * 2nd param contains SKU or barcode
    // * 3rd param shows if barcode is used
    if ($productId == null) {
      $productId = $identifierType;
      $identifierType = 'sku';
    }
    else if ($identifierType)
      $identifierType = strtolower(trim($identifierType));

    $helper = Mage::helper('mventory/product_attribute');

    $productId = $helper->getProductId($productId, $identifierType);

    if (!$productId)
      $this->_fault('product_not_exists');

    $website = Mage::helper('mventory/product')->getWebsite($productId);
    $storeId = $website
                 ->getDefaultStore()
                 ->getId();

    $product = $this->_getProduct($productId, $storeId, $identifierType);

    //Product's ID can be changed by '_getProduct()' function if original
    //product is configurable one
    $productId = $product->getId();

    $_result = array(
      'product_id' => $productId,
      'sku' => $product->getSku(),
      'set' => $product->getAttributeSetId(),
      'websites' => $product->getWebsiteIds(),
      'category_ids' => $product->getCategoryIds()
    );

    $editableAttributes = $product
      ->getTypeInstance(true)
      ->getEditableAttributes($product);

    foreach ($editableAttributes as $attribute) {
      $code = $attribute->getAttributeCode();

      if (isset($_result[$code]))
        continue;

      if (!$this->_isAllowedAttribute($attribute))
        continue;

      $_result[$code] = $product->getData($code);
    }

    unset($editableAttributes, $attribute, $code);

    $result = array_intersect_key(
      $_result,
      array_merge(
        array(
          'product_id' => true,
          'set' => true,
          'websites' => true,
          'url_path' => true,
          'status' => true,
          'category_ids' => true,
          'mv_created_date' => true,
          'mv_created_userid' => true,
          'mv_stock_journal' => true,
          'created_at' => true,
          'updated_at' => true
        ),
        $helper->getEditables($_result['set'])
      )
    );

    $stockItem = Mage::getModel('mventory/stock_item_api');

    $_result = $stockItem->items($productId);

    if (isset($_result[0]))
      $result = array_merge($result, $_result[0]);

    $productAttributeMedia
      = Mage::getModel('mventory/product_attribute_media_api');

    $baseUrlPath = Mage_Core_Model_Store::XML_PATH_UNSECURE_BASE_URL;

    $mediaConfig = Mage::getSingleton('catalog/product_media_config');

    $baseMediaPath = $mediaConfig->getBaseMediaPath();
    $baseMediaUrl = Mage::getStoreConfig($baseUrlPath, $storeId)
                    . 'media/'
                    . $mediaConfig->getBaseMediaUrlAddition();

    $images = $productAttributeMedia->items($productId, $storeId, 'id');

    foreach ($images as &$image) {
      $_image = new Varien_Image($baseMediaPath . $image['file']);

      $image['url'] = $baseMediaUrl . $image['file'];
      $image['width'] = (string) $_image->getOriginalWidth();
      $image['height'] = (string) $_image->getOriginalHeight();
    }

    $result['images'] = $images;

    $helper = Mage::helper('mventory/product_configurable');

    if ($siblingIds = $helper->getSiblingsIds($productId)) {
      $attrs = Mage::getModel('mventory/product_attribute_api')
        ->fullInfoList($result['set']);

      foreach ($attrs as $attr)
        if ($attr['is_configurable'])
          break;

      $siblings = Mage::getResourceModel('catalog/product_collection')
                    ->addAttributeToSelect('price')
                    ->addAttributeToSelect('name')
                    ->addAttributeToSelect($attr['attribute_code'])
                    ->addIdFilter($siblingIds)
                    ->addStoreFilter($storeId)
                    ->setFlag('require_stock_items');

      foreach ($siblings as $sibling)
        $result['siblings'][] = array(
          'product_id' => $sibling->getId(),
          'sku' => $sibling->getSku(),
          'name' => $sibling->getName(),
          'price' => $sibling->getPrice(),
          'qty' => $sibling->getStockItem()->getQty(),
          $attr['attribute_code'] => $sibling->getData($attr['attribute_code'])
        );
    }

    $product = new Varien_Object($result);

    Mage::dispatchEvent(
      'mventory_api_product_info',
      array('product' => $product, 'website' => $website)
    );

    return $helper->prepareApiResponse($product->getData());
  }

  public function limitedList ($name = null, $categoryId = null, $page = 1) {
    $helper = Mage::helper('mventory');
    $storeId = $helper->getCurrentStoreId();

    $limit = (int) Mage::getStoreConfig(
      MVentory_API_Model_Config::_FETCH_LIMIT,
      $storeId
    );

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

    if ($name) {
      $tmpl = '%' . $name . '%';

      //Use 'left' join to include products without record
      //for value of barcode attribute in DB
      $collection->addAttributeToFilter(
        array(
          array('attribute' => 'name', 'like' => $tmpl),
          array('attribute' => 'sku', 'like' => $tmpl),
          array('attribute' => 'product_barcode_', 'like' => $tmpl)
        ),
        null,
        'left'
      );
    }

    $collection
      ->addAttributeToSelect('name')
      ->addAttributeToFilter(
          'type_id',
          Mage_Catalog_Model_Product_Type::TYPE_SIMPLE
        )
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

    return $helper->prepareApiResponse($result);
  }

  public function createAndReturnInfo ($type, $set, $sku, $data,
                                       $storeId = null) {

    $helper = Mage::helper('mventory/product_configurable');

    $hasProduct = ($id = $helper->getProductId($sku, 'sku'))
                  && Mage::getModel('catalog/product')
                       ->load($id)
                       ->getId();

    if (!$hasProduct) {
      $sid = isset($data['_api_link_with_product'])
               ? $data['_api_link_with_product']
                 : false;

      $data = $this->_filterData($data, $set);

      //Set status to Enabled if it's not set, because Magento doesn't show
      //newly created product in the admin interface if it's omitted
      if (!isset($data['status']))
        $data['status'] = 1;

      $data['mv_created_userid'] = $helper->getApiUser()->getId();
      $data['mv_created_date'] = time();
      $data['website_ids'] = $helper->getWebsitesForProduct();

      $website = $helper->getCurrentWebsite();

      //Set visibility to website's default value
      $data['visibility'] = $helper->getDefaultVisibility($website);

      $data['tax_class_id'] = (int) $helper->getConfig(
        MVentory_API_Model_Config::_TAX_CLASS,
        $website
      );

      Mage::dispatchEvent(
        'mventory_api_product_create',
        array(
          'product' => ($product = new Varien_Object($data)),
          'website' => $website
        )
      );

      //Use admin store ID to save values of attributes in the default scope
      $id = $this->create(
        $type,
        $set,
        $sku,
        $product->getData(),
        Mage_Core_Model_App::ADMIN_STORE_ID
      );

      //Use admin store to save values of attributes in the default scope
      $product = $this->_getProduct(
        $id,
        Mage_Core_Model_App::ADMIN_STORE_ID,
        'id'
      );

      //!!!TODO: consider to move it before creating product
      $saveProduct = $this->_matchCategory($product);

      $saveProduct |= $sid
                      && ($sid = $helper->getProductId($sid))
                      && $helper->link($product, $sid);

      if ($saveProduct)
        $product->save();
    } else if (isset($data['_api_update_if_exists'])
              && $data['_api_update_if_exists']) {

      $data['set'] = $set;

      $this->update($id, $data, null, 'id');
    }

    return $this->fullInfo($id, 'id');
  }

  public function duplicateAndReturnInfo ($oldSku,
                                          $newSku,
                                          $data = array(),
                                          $mode = 'all',
                                          $subtractQty = 0) {

    $newId = Mage::helper('mventory/product')->getProductId($newSku, 'sku');

    if ($newId)
      return $this->fullInfo($newId, 'id');

    $old = $this->_getProduct($oldSku, null, 'sku');
    $oldId = $old->getId();

    //Load images from the original product before duplicating
    //because the original one can be removed during duplication
    //if duplicated product is similar to it.
    $images = Mage::getModel('mventory/product_attribute_media_api');
    $oldImages = $images->items($oldId);

    $subtractQty = (int) $subtractQty;

    if ($subtractQty > 0) {
      $stock = Mage::getModel('cataloginventory/stock_item')
                 ->loadByProduct($oldId);

      if ($stock->getId())
        $stock
          ->subtractQty($subtractQty)
          ->save();

      unset($stock);
    }

    $data = $this->_filterData($data, $old->getAttributeSetId());

    if (!isset($data['sku']))
      $data['sku'] = $newSku;

    //Set visibility to "Catalog, Search". By default all products are visible.
    //They will be hidden if configurable one is created.
    $data['visibility'] = 4;

    $new = $old
            ->setData('mventory_update_duplicate', $data)
            ->duplicate();

    $newId = $new->getId();

    if ($new->getData('mventory_assigned_to_configurable_after'))
      return $this->fullInfo($newId, 'id');

    unset($new);

    $mode = strtolower($mode);

    $old = $oldImages;
    $new = $images->items($newId);

    $countOld = count($old);
    $countNew = count($new);

    for ($n = 0; $n < $countOld && $n < $countNew; $n++) {
      $file = $new[$n]['file'];

      if ($mode == 'none') {
        $images->remove_($newId, $file);

        continue;
      }

      if (!isset($old[$n]['types'])) {
        if ($mode == 'main')
          $images->remove_($newId, $file);

        continue;
      }

      $types = $old[$n]['types'];

      if ($mode == 'main' && !in_array('image', $types)) {
        $images->remove_($newId, $file);

        continue;
      }

      $images->update($newId, $file, array('types' => $types));
    }

    return $this->fullInfo($newId, 'id');
  }

  /**
   * Get info about new products, sales and stock
   *
   * @return array
   */
  public function statistics () {
    $helper = Mage::helper('mventory');
    $storeId = $helper->getCurrentStoreId();
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
      ->columns(array('COUNT(at_qty.qty) AS total_qty',
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

    return $helper->prepareApiResponse(array('day_sales' => (double)$daySales,
                 'week_sales' => (double)$weekSales,
                 'month_sales' => (double)$monthSales,
                 'total_sales' => (double)$totalSales,
                 'total_stock_qty' => (double)$totalStockQty,
                 'total_stock_value' => (double)$totalStockValue,
                 'day_loaded' => (double)$dayLoaded,
                 'week_loaded' => (double)$weekLoaded,
                 'month_loaded' => (double)$monthLoaded));
  }

  /**
   * Update product data
   *
   * Method is redefined to:
   *   - Update product's attribute set
   *   - Process additional SKUs
   *
   * @param int|string $productId
   * @param array $productData
   * @param string|int $store
   * @param string $identifierType Type of $productId parameter
   * @return boolean
   */
  public function update ($productId,
                          $productData,
                          $store = null,
                          $identifierType = null) {

    //Use admin store ID to save values of attributes in the default scope
    $product = $this->_getProduct(
      $productId,
      Mage_Core_Model_App::ADMIN_STORE_ID,
      $identifierType
    );

    //!!!TODO: do we want to update attr set in linked prods?
    //Update attribute set in the product and set flag to remove old values
    //from the DB if it's set in incoming data and is different
    //from current one.
    if (isset($productData['set'])
        && ($newSet = (int) $productData['set'])
        && ($oldSet = (int) $product->getAttributeSetId())
        && ($removeOldValues = $newSet != $oldSet)) {

      $this->_checkProductAttributeSet($newSet);

      $product->setAttributeSetId($newSet);
    }

    $skus = isset($productData['additional_sku'])
              ? (array) $productData['additional_sku']
                : false;

    $removeSkus = isset($productData['stock_data']['qty'])
                  && $productData['stock_data']['qty'] == 0;

    if ($skus)
      unset($productData['stock_data']);

    $sid = isset($productData['_api_link_with_product'])
             ? $productData['_api_link_with_product']
               : false;

    $productData = $this->_filterData(
      $productData,
      $product->getAttributeSetId()
    );

    $this->_prepareDataForSave($product, $productData);

    if (isset($removeOldValues) && $removeOldValues)
      $this->_removeOldValues($product, $oldSet, $newSet);

    $this->_matchCategory($product);

    if ($sid) {
      $helper = Mage::helper('mventory/product_configurable');

      if ($sid = $helper->getProductId($sid)) try {
        $helper->link($product, $sid);
      } catch (Exception $e) {
        $this->_fault('linking_problems', $e->getMessage());
      }
    } else {
      $helper = Mage::helper('mventory/product_configurable');

      if ($cID = $helper->getIdByChild($product))
        $helper->update($product, $cID);
    }

    try {
      if (is_array($errors = $product->validate())) {
        $_errors = array();
        $cHelper = Mage::helper('catalog');

        foreach ($errors as $code => $error)
          $_errors[]
            = $error === true
                ? $cHelper->__(self::__INVALID_VAL, $code)
                  : $cHelper->__(self::__INVALID_VAL_ERR, $code, $error);

        $this->_fault('data_invalid', implode("\n", $_errors));
      }

      $product->save();
    } catch (Mage_Core_Exception $e) {
      $this->_fault('data_invalid', $e->getMessage());
    }

    $productId = $product->getId();

    if ($removeSkus)
      Mage::getResourceModel('mventory/sku')->removeByProductId($productId);

    if ($skus) {
      Mage::getResourceModel('mventory/sku')->add(
        $skus,
        $productId,
        Mage::helper('mventory/product')->getCurrentWebsite()
      );

      $stock = Mage::getModel('cataloginventory/stock_item')
                 ->loadByProduct($productId);

      if ($stock->getId())
        $stock
          ->addQty(count($skus))
          ->save();
    }

    return true;
  }

  /**
   * Delete product
   *
   * @param int|string $productId
   * @return boolean
   */
  public function delete ($productId, $identifierType = null) {
    $product = $this->_getProduct($productId, null, $identifierType);
    $helper = Mage::helper('mventory/product_configurable');

    try {
      if ($cID = $helper->getIdByChild($product))
        $helper->remove($product, $cID);

      $product->delete();
    } catch (Mage_Core_Exception $e) {
      $this->_fault('not_deleted', $e->getMessage());
    }

    return true;
  }

  /**
   * Return loaded product instance
   *
   * The function is redefined to load product by barcode or additional SKUs
   * or load children product if specified product is configurable
   *
   * @param  int|string $productId (SKU or ID)
   * @param  int|string $store
   * @param  string $identifierType
   * @return Mage_Catalog_Model_Product
   */
  protected function _getProduct($productId, $store = null,
                                 $identifierType = null) {

    $helper = Mage::helper('mventory/product');

    $productId = $helper->getProductId($productId, $identifierType);

    if (!$productId)
      $this->_fault('product_not_exists');

    $helper = Mage::helper('mventory/product_configurable');

    //Load details of assigned product if the product is configurable
    if ($childrenIds = $helper->getChildrenIds($productId))
      $productId = current($childrenIds);

    $product = Mage::getModel('catalog/product')
                 ->setStoreId(Mage::app()->getStore($store)->getId())
                 ->load($productId);

    if (!$product->getId())
      $this->_fault('product_not_exists');

    return $product;
  }

  /**
   * Remove data of attributes from the old attribute set
   * which are not presence in the new set
   *
   * @param Mage_Catalog_Model_Product $product
   * @param int $oldSet Old attribute sed ID
   * @param int $newSet New attribute sed ID
   */
  protected function _removeOldValues ($product, $oldSet, $newSet) {
    $type = $product->getTypeId();

    if (!$oldAttrs = $this->getAdditionalAttributes($type, $oldSet))
      return;

    $newAttrs = $this->getAdditionalAttributes($type, $newSet);

    foreach ($newAttrs as $attr)
      $_newAttrs[$attr['code']] = true;

    foreach ($oldAttrs as $oldAttr) {
      $code = $oldAttr['code'];

      if (!isset($_newAttrs[$code]))
        $product->setData($code, false);
    }
  }

  protected function _matchCategory ($product) {
    $result = Mage::getModel('mventory/matching')->matchCategory($product);

    if (!$result)
      return;

    $product->setCategoryIds($result);

    return true;
  }

  protected function _filterData ($data, $setId) {
    if (!$data)
      return $data;

    return array_intersect_key(
      $data,
      array_merge(
        Mage::helper('mventory/product_attribute')->getWritables($setId),
        $this->_allowUpdate
      )
    );
  }
}
