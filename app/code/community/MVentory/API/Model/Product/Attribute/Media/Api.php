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
 * Catalog product media api
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Model_Product_Attribute_Media_Api
  extends Mage_Catalog_Model_Product_Attribute_Media_Api {

  public function createAndReturnInfo ($productId, $data, $storeId = null,
                                       $identifierType = null) {

    if (!isset($data['file']))
      $this->_fault('data_invalid',
                    Mage::helper('catalog')->__('The image is not specified.'));

    $file = &$data['file'];

    if (!isset($file['name'], $file['mime'], $file['content']))
      $this->_fault('data_invalid',
                    Mage::helper('catalog')->__('The image is not specified.'));


    if (!isset($this->_mimeTypes[$file['mime']]))
      $this->_fault('data_invalid',
                    Mage::helper('catalog')->__('Invalid image type.'));

    if (!$file['content'] = @base64_decode($file['content'], true))
      $this->_fault(
        'data_invalid',
        Mage::helper('catalog')
          ->__('The image contents is not valid base64 data.')
      );

    $file['name'] = strtolower(trim($file['name']));

    //$storeId = Mage::helper('mventory')->getCurrentStoreId($storeId);

    //Temp solution, apply image settings globally
    $storeId = null;

    $images = $this->items($productId, $storeId, $identifierType);

    $name = $file['name'] . '.' . $this->_mimeTypes[$file['mime']];

    foreach ($images as $image)
      //Throw of first 5 symbols becau se 'file'
      //has following format '/i/m/image.ext' (dispretion path)
      if (strtolower(substr($image['file'], 5)) == $name)
        return Mage::getModel('mventory/product_api')
                 ->fullInfo($productId, $identifierType);

    $hasMainImage = false;
    $hasSmallImage = false;
    $hasThumbnail = false;

    if (isset($image['types']))
      foreach ($images as $image) {
        if (in_array('image', $image['types']))
          $hasMainImage = true;

        if (in_array('small_image', $image['types']))
          $hasSmallImage = true;

        if (in_array('thumbnail', $image['types']))
          $hasThumbnail = true;
      }

    if (!$hasMainImage)
      $data['types'][] = 'image';

    if (!$hasSmallImage)
      $data['types'][] = 'small_image';

    if (!$hasThumbnail)
      $data['types'][] = 'thumbnail';

    //We don't use exclude feature
    $data['exclude'] = 0;

    $file['content'] = base64_encode(
      $this->_fixOrientation($name, $file['content'])
    );

    $this->create($productId, $data, $storeId, $identifierType);

    $productApi = Mage::getModel('mventory/product_api');

    //Set product's visibility to 'catalog and search' if product doesn't have
    //small image before addind the image
    if (!$hasSmallImage)
      $productApi->update(
        $productId,
        array(
          'visibility' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH
        ),
        null,
        $identifierType
      );

    $helper = Mage::helper('mventory/product_configurable');

    if (($productId = $helper->getProductId($productId, $identifierType))
        && $cID = $helper->getIdByChild($productId))
      $this->_sync($productId, $cID, $helper);

    return $productApi->fullInfo($productId, $identifierType);
  }

  /**
   * Update image data
   *
   * @param int|string $id
   * @param string $file
   * @param array $data
   * @param string|int $store
   * @param string $idType
   * @return boolean
   */
  public function update ($id, $file, $data, $store = null, $idType = null) {
    $data = $this->_prepareImageData($data);

    //Temp solution, apply image settings globally
    $store = null;
    $product = $this->_initProduct($id, $store, $idType);
    $id = $product->getId();

    $backend = $this
      ->_getGalleryAttribute($product)
      ->getBackend();

    if (!$backend->getImage($product, $file))
      $this->_fault('not_exists');

    if (isset($data['file']['mime']) && isset($data['file']['content'])) {
      if (!isset($this->_mimeTypes[$data['file']['mime']]))
        $this->_fault(
          'data_invalid',
          Mage::helper('catalog')->__('Invalid image type.')
        );

      if (!$fileContent = @base64_decode($data['file']['content'], true))
        $this->_fault(
          'data_invalid',
          Mage::helper('catalog')->__('Image content is not valid base64 data.')
        );

      unset($data['file']['content']);

      $ioAdapter = new Varien_Io_File();

      try {
        $fileName = Mage::getBaseDir('media')
                    . DS . 'catalog'
                    . DS . 'product'
                    . $file;

        $ioAdapter->open(array('path'=>dirname($fileName)));
        $ioAdapter->write(basename($fileName), $fileContent, 0666);
      } catch(Exception $e) {
        $this->_fault(
          'not_created',
          Mage::helper('catalog')->__('Can\'t create image.')
        );
      }
    }

    $backend->updateImage($product, $file, $data);

    if (isset($data['types']) && is_array($data['types'])) {
      $oldTypes = array();

      foreach ($product->getMediaAttributes() as $attribute)
        if ($product->getData($attribute->getAttributeCode()) == $file)
          $oldTypes[] = $attribute->getAttributeCode();

      $clear = array_diff($oldTypes, $data['types']);

      if (count($clear) > 0)
        $backend->clearMediaAttribute($product, $clear);

      $backend->setMediaAttribute($product, $data['types'], $file);

      $helper = Mage::helper('mventory/product_configurable');


      if ($cID = $helper->getIdByChild($id)) {
        $ids = $helper->getChildrenIds($cID);

        unset($ids[$id]);
        $ids[$cID] = $cID;

        $mediaVals = array();

        foreach ($product->getMediaAttributes() as $attr)
          $mediaVals[$attr->getAttributeId()] = $file;

        Mage::getResourceSingleton('catalog/product_action')
          ->updateAttributes($ids, $mediaVals, $product->getStoreId());
      }
    }

    try {
      $product->save();
    } catch (Mage_Core_Exception $e) {
      $this->_fault('not_updated', $e->getMessage());
    }

    return true;
  }

  public function remove_ ($productId, $file, $identifierType = null) {
    $image = $this->info($productId, $file, null, $identifierType);

    $this->remove($productId, $file, $identifierType);
    $images = $this->items($productId, null, $identifierType);

    $helper = Mage::helper('mventory/product_configurable');

    if (($productId = $helper->getProductId($productId, $identifierType)))
      $cID = $helper->getIdByChild($productId);

    $productApi = Mage::getModel('mventory/product_api');

    if (!$images) {
      if (!(isset($cID) && $cID))
        $productApi->update(
          $productId,
          array('visibility' => (int) $helper->getConfig(
            MVentory_API_Model_Config::_API_VISIBILITY,
            $helper->getWebsite($productId)
          )),
          null,
          $identifierType
        );
    } else if (in_array('image', $image['types'])) {
      $this->update(
        $productId,
        $images[0]['file'],
        array(
          'types' => array('image', 'small_image', 'thumbnail'),
          'exclude' => 0
        ),
        null,
        $identifierType
      );
    }

    if (isset($cID) && $cID)
      $this->_sync($productId, $cID, $helper);

    return $productApi->fullInfo($productId, $identifierType);
  }

  /**
   * Retrieve product
   *
   * The function is redefined to allow loading product by additional SKU
   * or barcode
   *
   * @param int|string $productId
   * @param string|int $store
   * @param  string $identifierType
   * @return Mage_Catalog_Model_Product
   */
  protected function _initProduct($productId, $store = null,
                                  $identifierType = null) {

    $helper = Mage::helper('mventory/product');

    $productId = $helper->getProductId($productId, $identifierType);

    if (!$productId)
      $this->_fault('product_not_exists');

    $product = Mage::getModel('catalog/product')
                 ->setStoreId(Mage::app()->getStore($store)->getId())
                 ->load($productId);

    if (!$product->getId())
      $this->_fault('product_not_exists');

    return $product;
  }

  private function _fixOrientation ($name, &$data) {
    $io = new Varien_Io_File();

    $tmp  = Mage::getBaseDir('var')
            . DS
            . 'api'
            . DS
            . $this->_getSession()->getSessionId();

    try {
      $io->checkAndCreateFolder($tmp);
      $io->open(array('path' => $tmp));
      $io->write($name, $data, 0666);

      $path = $tmp . DS . $name;

      if (Mage::helper('mventory/image')->fixOrientation($path))
        $data = $io->read($path);
    } catch (Exception $e) {}

    $io->rmdir($tmp, true);

    return $data;
  }

  protected function _sync ($aID, $cID, $helper) {
    Mage::log('_sync()');

    $ids = $helper->getChildrenIds($cID);

    //Add ID of configurable (C) product to load it; unset ID of currently
    //updating product because it's been already loaded above
    $ids[$cID] = $cID;

    $prods = Mage::getResourceModel('catalog/product_collection')
      ->addAttributeToSelect('*')
      ->addIdFilter($ids)
      ->addStoreFilter($helper->getCurrentWebsite()->getDefaultStore())
      ->getItems();

    $a = $prods[$aID];
    $c = $prods[$cID];
    unset($prods[$aID], $prods[$cID]);

    Mage::helper('mventory/image')->sync($a, $c, $prods);
  }
}
