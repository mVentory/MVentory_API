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
 * Event handlers
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Model_Observer {

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

  public function addProductMassactions ($observer) {
    $helper = Mage::helper('mventory');
    $block = $observer
      ->getBlock()
      ->getMassactionBlock();

    $block
      ->addItem(
          'namerebuild',
          array(
            'label' => $helper->__('Rebuild product name'),
            'url' => $block->getUrl(
              'adminhtml/mventory_catalog_product/massNameRebuild',
              array('_current' => true)
            )
          )
        )
      ->addItem(
          'categorymatch',
          array(
            'label' => $helper->__('Match product category'),
            'url' => $block->getUrl(
              'adminhtml/mventory_catalog_product/massCategoryMatch',
              array('_current' => true)
            )
          )
        )
      ->addItem(
          'imagesync',
          array(
            'label' => $helper->__('Sync product images'),
            'url' => $block->getUrl(
              'mventory/catalog_product/massImageSync',
              array('_current' => true)
            ),
            'additional' => array(
              'mode' => array(
                'name' => 'mode',
                'type' => 'select',
                'label' => $helper->__('Mode'),
                'values' => array(
                  array(
                    'value' => '',
                    'label' => $helper->__('Sync all')
                  ),
                  array(
                    'value' => 'empty',
                    'label' => $helper->__('Sync only products w/o images')
                  ),
                  array(
                    'value' => 'source',
                    'label' => $helper->__('Configurable as source of images')
                  ),
                  array(
                    'value' => 'source,empty',
                    'label' => $helper->__(
                      'Configurable as source & only w/o images'
                    )
                  )
                )
              )
            )
          )
        );
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

    $attribute = Mage::helper('mventory/product_attribute')
      ->getConfigurable($product->getAttributeSetId());

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

  /**
   * Add "Generate mVentory Access Link" button on API user pages
   * Event: controller_action_layout_render_before_adminhtml_api_user_edit
   */
  public function addCreateApiUserButton ($observer) {

    //Check if API user loaded and exists
    $apiUser = Mage::registry('api_user');
    if (!($apiUser && $apiUser->getId()))
      return;

    $children = Mage::getSingleton('core/layout')
      ->getBlock('content')
      ->getChild();

    foreach ($children as $key => $block)
      if ($block instanceof Mage_Adminhtml_Block_Api_User_Edit) {
        Mage::helper('mventory/access')->addGenerateLinkButton(
          $block,
          $apiUser
        );

        return;
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
