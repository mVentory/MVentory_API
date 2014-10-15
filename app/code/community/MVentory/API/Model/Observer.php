<?php

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License BY-NC-ND.
 * NonCommercial — You may not use the material for commercial purposes.
 * NoDerivatives — If you remix, transform, or build upon the material,
 * you may not distribute the modified material.
 * See the full license at http://creativecommons.org/licenses/by-nc-nd/4.0/
 *
 * See http://mventory.com/legal/licensing/ for other licensing options.
 *
 * @package MVentory/API
 * @copyright Copyright (c) 2014 mVentory Ltd. (http://mventory.com)
 * @license http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

/**
 * Event handlers
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Model_Observer {
  const __CONFIG_URL = <<<'EOT'
mVentory configuration URL: <a href="%1$s">%1$s</a> (Can only be used once and is valid for %2$d hours)
EOT;

  public function productInit ($observer) {
    $product = $observer->getProduct();

    $categories = $product->getCategoryIds();

    if (!count($categories))
      return;

    $categoryId = $categories[0];

    $lastId = Mage::getSingleton('catalog/session')->getLastVisitedCategoryId();

    $category = $product->getCategory();

    //Return if last visited vategory was not used
    if ($category && $category->getId() != $lastId)
      return;

    //Return if categories are same, nothing to change
    if ($lastId == $categoryId)
      return;

    if (!$product->canBeShowInCategory($categoryId))
      return;

    $category = Mage::getModel('catalog/category')->load($categoryId);

    $product->setCategory($category);

    Mage::unregister('current_category');
    Mage::register('current_category', $category);
  }

  public function addProductNameRebuildMassaction ($observer) {
    $block = $observer->getBlock();

    $route = 'mventory/catalog_product/massNameRebuild';

    $label = Mage::helper('mventory')->__('Rebuild product name');
    $url = $block->getUrl($route, array('_current' => true));

    $block
      ->getMassactionBlock()
      ->addItem('namerebuild', compact('label', 'url'));
  }

  /**
   * Add action "Populate product attributes" to admin product manage grid
   */
  public function addProductAttributesPopulateMassaction ($observer) {
    $block = $observer->getBlock();

    $route = 'mventory/catalog_product/massAttributesPopulate';

    $label = Mage::helper('mventory')->__('Populate product attributes');
    $url = $block->getUrl($route, array('_current' => true));

    $block
      ->getMassactionBlock()
      ->addItem('attributespopulate', compact('label', 'url'));
  }

  public function addProductCategoryMatchMassaction ($observer) {
    $block = $observer->getBlock();

    $route = 'mventory/catalog_product/massCategoryMatch';

    $label = Mage::helper('mventory')->__('Match product category');
    $url = $block->getUrl($route, array('_current' => true));

    $block
      ->getMassactionBlock()
      ->addItem('categorymatch', compact('label', 'url'));
  }

  /**
   * Unset is_duplicate flag to prevent coping image files
   * in Mage_Catalog_Model_Product_Attribute_Backend_Media::beforeSave() method
   *
   * @param Varien_Event_Observer $observer Event observer
   */
  public function unsetDuplicateFlagInProduct ($observer) {
    $observer
      ->getNewProduct()
      ->setIsDuplicate(false)
      ->setOrigIsDuplicate(true);
  }

  /**
   * Restore is_duplicate flag to not affect other code, such as in
   * Mage_Catalog_Model_Product_Attribute_Backend_Media::afterSave() method
   *
   * @param Varien_Event_Observer $observer Event observer
   */
  public function restoreDuplicateFlagInProduct ($observer) {
    $product = $observer->getProduct();

    if ($product->getOrigIsDuplicate())
      $product->setIsDuplicate(true);
  }

  public function addMatchingRulesBlock ($observer) {
    $content = Mage::app()
      ->getFrontController()
      ->getAction()
      ->getLayout()
      ->getBlock('content');

    $matching = $content->getChild('mventory.matching');

    $content
      ->unsetChild('mventory.matching')
      ->append($matching);
  }

  public function updateDuplicate ($observer) {
    $data = $observer
              ->getCurrentProduct()
              ->getData('mventory_update_duplicate');

    if ($data)
      $observer
        ->getNewProduct()
        ->addData($data);
  }

  public function updatePricesInConfigurableOnStockChange ($observer) {
    $item = $observer->getItem();

    if (!$item->getManageStock())
      return;

    $origStatus = $item->getOrigData('is_in_stock');
    $status = $item->getData('is_in_stock');

    if ($origStatus !== null && $origStatus == $status)
      return;

    $product = $item->getProduct();

    if (!$product)
      $product = Mage::getModel('catalog/product')->load($item->getProductId());

    if (!$product->getId())
      return;

    $helper = Mage::helper('mventory/product_configurable');

    if (!$childrenIds = $helper->getSiblingsIds($product))
      return;

    $storeId = Mage::app()
                 ->getStore(true)
                 ->getId();

    if ($storeId != Mage_Core_Model_App::ADMIN_STORE_ID)
      Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

    $configurable = Mage::getModel('catalog/product')
                      ->load($helper->getIdByChild($product));

    if ($storeId != Mage_Core_Model_App::ADMIN_STORE_ID)
      Mage::app()->setCurrentStore($storeId);

    if (!$configurable->getId())
      return;

    $attribute = $helper->getConfigurableAttribute(
                   $product->getAttributeSetId()
                 );

    $children = Mage::getResourceModel('catalog/product_collection')
                  ->addAttributeToSelect(array(
                      'price',
                      $attribute->getAttributeCode()
                    ))
                  ->addIdFilter($childrenIds);

    Mage::getResourceModel('cataloginventory/stock')
      ->setInStockFilterToCollection($children);

    if ($status)
      $children->addItem($product);

    $helper->recalculatePrices($configurable, $attribute, $children);

    $configurable->save();
  }

  public function generateLinkForProfile ($observer) {
    $helper = Mage::helper('mventory');

    if (!$customer = $helper->getCustomerByApiUser($observer->getObject()))
      return;

    if (($websiteId = $customer->getWebsiteId()) === null)
      return;

    $store = Mage::app()
      ->getWebsite($websiteId)
      ->getDefaultStore();

    if ($store->getId() === null)
      return;

    $period = $store->getConfig(MVentory_API_Model_Config::_LINK_LIFETIME) * 60;

    if (!$period)
      return;

    $key = strtr(base64_encode(mcrypt_create_iv(12)), '+/=', '-_,');

    $customer
      ->setData(
          'mventory_app_profile_key',
          $key . '-' . (microtime(true) + $period)
        )
      ->save();

    $msg = $helper->__(
      self::__CONFIG_URL,
      $store->getBaseUrl() . 'mventory-key/' . urlencode($key),
      round($period / 3600)
    );

    Mage::getSingleton('adminhtml/session')->addNotice($msg);
  }

  public function addCreateApiUserButton ($observer) {
    $block = $observer->getData('block');

    if ($block instanceof Mage_Adminhtml_Block_Customer_Edit) {
      $url = $block->getUrl(
        'mventory/customer/createapiuser',
        array(
          '_current' => true,
          'id' => $block->getCustomerId(),
          'tab' => '{{tab_id}}'
        )
      );

      $helper = Mage::helper('mventory');

      $block->addButton(
        'create_api_user',
        array(
          'label' => $helper->__('mVentory Access'),
          'onclick' => sprintf(
            'if (confirm(\'%s\')) setLocation(mv_prepare_url(\'%s\'))',
            $helper->__('Allow database access via mVentory app?'),
            $url
          ),
          'class' => 'add'
        ),
        -1
      );
    }
  }

  /**
   * Observer for catalog_entity_attribute_save_before event in adminhtml area
   * Serialise array of metadata for the app if it's set
   *
   * @param Varien_Event_Observer $observer
   */
  public function saveAttrMetadata ($observer) {
    $attr = $observer->getData('attribute');

    if (!(isset($attr['mventory_metadata'])
          && is_array($attr['mventory_metadata'])))
      return;

    $metadata = $attr['mventory_metadata'];

    //Reset selected sites if Visible in all option is also selected.
    //!!!TODO: This should be done properly via option's backend
    //but it's not supported. Also default value for the options
    //should be fetched from the config
    if (isset($metadata['invisible_for_websites'])
        && is_array($metadata['invisible_for_websites'])
        && $metadata['invisible_for_websites']
        && in_array('', $metadata['invisible_for_websites']))
      $metadata['invisible_for_websites'] = array('');

    $attr['mventory_metadata'] = serialize($metadata);
  }
}
