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
   * @param bool $slave Shows that $product will be used as a source of changes
   *                    or not
   * @return MVentory_API_Helper_Image
   */
  public function sync ($product, $configurable, $ids, $slave = false) {
    $attrs = $product->getAttributes();

    if (!isset($attrs['media_gallery']))
      return $this;

    $galleryAttribute = $attrs['media_gallery'];
    $galleryAttributeId = $galleryAttribute->getAttributeId();

    unset($attrs);

    $storeId = $product->getStoreId();
    $pID = $product->getId();

    //Collect IDs of all products
    $ids = array_unique(array_merge(
      array($configurable->getId(), $pID),
      array_keys($ids)
    ));

    //Store selected images (image, small_image, thumbnail)
    $mediaAttributes = $product->getMediaAttributes();

    foreach ($mediaAttributes as $code => $attr)
      if ($configurable->getData($code) != 'no_selection')
        $mediaVals[$attr->getAttributeId()] = $configurable->getData($code);

    if (!(isset($mediaVals) && count($mediaVals) == count($mediaAttributes)))
      foreach ($mediaAttributes as $code => $attr)
        $mediaVals[$attr->getAttributeId()] = $product->getData($code);

    unset($product, $mediaAttributes);

    $object = new Varien_Object();
    $object->setAttribute($galleryAttribute);

    $product = new Varien_Object();
    $product->setStoreId($storeId);

    $resourse
      = Mage::getResourceSingleton('catalog/product_attribute_backend_media');

    $imgs = array();

    foreach ($ids as $id)
      if ($gallery = $resourse->loadGallery($product->setId($id), $object))
        foreach ($gallery as $img) {
          $file = $img['file'];

          $imgs[$file]['prods'][$id] = $img['value_id'];

          //Remember image settings from first product only, so later products
          //don't overwrite it. Allows to use image settings from source product
          //(which is first in the list) for images which will be added to
          //products
          if (!isset($imgs[$file]['img'])) {
            unset($img['value_id']);
            $imgs[$file]['img'] = $img;
          }
      }

    $nIds = count($ids);

    foreach ($imgs as $file => $data) {
      $_ids = $data['prods'];

      if (!($slave || isset($_ids[$pID]))) {
        foreach ($_ids as $valueId)
          $resourse->deleteGalleryValueInStore(
            $imgsToDel[] = $valueId,
            $storeId
          );

        unset($imgs[$file]);

        continue;
      }

      if (count($_ids) != $nIds) {
        $img = $data['img'];
        $val = array(
          'attribute_id' => $galleryAttributeId,
          'value' => $file
        );

        foreach (array_diff($ids, array_keys($_ids)) as $id) {
          $val['entity_id'] = $id;
          $img['value_id'] = $resourse->insertGallery($val);
          $resourse->insertGalleryValueInStore($img);
        }
      }
    }

    if (isset($imgsToDel))
      $resourse->deleteGallery($imgsToDel);

    if ($imgs) {
      reset($imgs);
      $firstImg = key($imgs);
    } else
      $firstImg = 'no_selection';

    foreach ($mediaVals as $id => $val)
      if (!isset($imgs[$val]))
        $mediaVals[$id] = $firstImg;

    Mage::getResourceSingleton('catalog/product_action')
      ->updateAttributes($ids, $mediaVals, $storeId);

    return $this;
  }
}
