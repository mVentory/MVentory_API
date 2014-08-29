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
 * Image helper
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Helper_Image extends MVentory_API_Helper_Product {

  private $_supportedTypes = array(
    IMAGETYPE_GIF => 'gif',
    IMAGETYPE_JPEG => 'jpeg',
    IMAGETYPE_PNG => 'png'
  );

  /**
   * Return list of distinct images of products
   *
   * @param array $products List of products
   *
   * @return array|null
   */
  public function getUniques ($products) {
    $backend = new Varien_Object();
    $backend->setData('attribute', $this->getMediaGalleryAttr());

    foreach ($products as $product)
      foreach ($this->getImages($product, $backend, false) as $image)
        $images[$image['file']] = $image;

    return isset($images) ? $images : null;
  }

  /**
   * Fixies orientation of the image using EXIF info
   *
   * @param strinf $file Path to image file
   * @return boolean|null Returns true if success
   */
  public function fixOrientation ($file) {
    if (!function_exists('exif_read_data'))
      return;

    if (($exif = exif_read_data($file)) === false)
      return;

    if (!isset($exif['FileType']))
      return;

    $type = $exif['FileType'];

    if (!array_key_exists($type, $this->_supportedTypes))
      return;

    if (isset($exif['Orientation']))
      $orientation = $exif['Orientation'];
    elseif (isset($exif['IFD0']['Orientation']))
      $orientation = $exif['IFD0']['Orientation'];
    else
      return;

    switch($orientation) {
      case 3: $angle = 180; break;
      case 6: $angle = -90; break;
      case 8: $angle = 90; break;
      default: return;
    }

    $typeName = $this->_supportedTypes[$type];

    $load = 'imagecreatefrom' . $typeName;
    $save = 'image' . $typeName;

    $save(imagerotate($load($file), $angle, 0), $file, 100);

    return true;
  }

  /**
   * Synching images between configurable product and all assigned products
   *
   * !!!TODO: make $product parameter optional, because there's sense to pass
   *          it only when images are updated/removed (e.g. when calling from
   *          media API)
   *
   * @param Mage_Catalog_Model_Product $product Currently updating product
   * @param Mage_Catalog_Model_Product $configurable Configurable product
   * @param array $_products List of other assigned products
   */
  public function sync ($product, $configurable, $_products) {
    $attrs = $product->getAttributes();

    if (!isset($attrs['media_gallery']))
      return;

    $galleryAttribute = $attrs['media_gallery'];
    $galleryAttributeId = $galleryAttribute->getAttributeId();

    unset($attrs);

    $helper = Mage::helper('mventory/product_configurable');
    $storeId = $product->getStoreId();
    $productId = $product->getId();
    $configurableId = $configurable->getId();

    //Collect IDs of all products
    $products = array(
      $productId => $productId,
      $configurableId => $configurableId,
    );

    foreach ($_products as $p)
      $products[$p->getId()] = $p->getId();

    //Store selected images (image, small_image, thumbnail)
    $mediaAttributes = $product->getMediaAttributes();

    foreach ($mediaAttributes as $code => $attr)
      $mediaValues[$attr->getAttributeId()] = $configurable->getData($code);

    unset($product, $mediaAttributes);

    $object = new Varien_Object();
    $object->setAttribute($galleryAttribute);

    $product = new Varien_Object();
    $product->setStoreId($storeId);

    $resourse
      = Mage::getResourceSingleton('catalog/product_attribute_backend_media');

    foreach ($products as $id => $images) {
      $gallery = $resourse->loadGallery($product->setId($id), $object);

      $products[$id] = array();

      if ($gallery) foreach ($gallery as $image) {
        $file = $image['file'];

        if (isset($image['removed']) && $image['removed']) {
          $imagesToDelete[$file] = true;

          continue;
        }

        if (isset($imagesToDelete[$file])) {
          $idsToDelete[] = $image['value_id'];

          continue;
        }

        $products[$id][$file] = $image;

        if (!isset($allImages[$file]))
          $allImages[$file] = $image;
      }
    }

    unset($imagesToDelete, $_images);

    if (isset($idsToDelete)) {
      foreach ($idsToDelete as $id)
        $resourse->deleteGalleryValueInStore($id, $storeId);

      $resourse->deleteGallery($idsToDelete);
    }

    unset($idsToDelete);

    if (isset($allImages)) foreach ($products as $id => $images) {
      foreach ($allImages as $file => $image) {
        if (!isset($images[$file]))
          $resourse->insertGalleryValueInStore(
            array(
              'value_id' => $resourse->insertGallery(
                array(
                  'entity_id' => $id,
                  'attribute_id' => $galleryAttributeId,
                  'value' => $file
                )
              ),
              'label'  => $image['label'],
              'position' => (int) $image['position'],
              'disabled' => (int) $image['disabled'],
              'store_id' => $storeId
            )
          );
      }
    }

    Mage::getResourceSingleton('catalog/product_action')
      ->updateAttributes(array_keys($products), $mediaValues, $storeId);
  }
}
