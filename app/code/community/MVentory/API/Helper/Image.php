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
 * @copyright Copyright (c) 2014-2015 mVentory Ltd. (http://mventory.com)
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
   * @param Mage_Catalog_Model_Product $product
   *   Currently updating product, optional
   *
   * @param Mage_Catalog_Model_Product $configurable
   *   Configurable product
   *
   * @param array $ids
   *   List of other assigned products
   *
   * @param array $params
   *   List of opional parameters:
   *     * slave - Shows that $product will be used as a source of changes
   *               or not. Default: false
   *     * empty - Update only products without images. Default: false
   *     * source - IDs of products among passed to the function to use as
   *                a source of images. Defaul: null
   *
   * @return MVentory_API_Helper_Image
   *
   * @todo this method should be updated to accept following parameters:
   *   * $configurable - configurable product is used as source of images and
   *       image settings for assigned products
   *   * $ids - List of assigned products
   *   * $params['changedProd'] - Simple product (one of $ids), optional
   *       parameter. If is set then is used as source instead of configurable
   *       product. This is needed to find which images should be removed
   *       and which images should be used as image, small image and thumbnail
   *       in other linked products after linked simple product is updated
   *       via API.
   */
  public function sync ($product, $configurable, $ids, $params = array()) {
    $params = array_merge(
      array(
        'slave' => false,
        'empty' => false,
        'source' => null
      ),
      $params
    );

    if ($params['source'])
      $params['source'] = array_flip((array) $params['source']);

    if ($product == null) {
      $product = $configurable;
      $params['slave'] = true;
    }

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
      if ($params['slave'] || $configurable->getData($code) != 'no_selection')
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
    $emptyIds = array();

    foreach ($ids as $id) {
      $gallery = $resourse->loadGallery($product->setId($id), $object);

      if (!$gallery && $params['empty']) {
        $emptyIds[] = $id;
        continue;
      }

      if ($params['source'] && !isset($params['source'][$id]))
        continue;

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
    }

    if (!$imgs)
      return $this;

    $ids = $params['empty'] ? $emptyIds : $ids;
    $nIds = count($ids);

    unset($emptyIds, $sourceIds);

    //Exit if it was requested to update only products without images
    //and there's no such products
    if (!$nIds && $params['empty'])
      return $this;

    foreach ($imgs as $file => $data) {
      $_ids = $data['prods'];

      if (!($params['slave'] || isset($_ids[$pID]))) {
        foreach ($_ids as $valueId)
          $resourse->deleteGalleryValueInStore(
            $imgsToDel[] = $valueId,
            $storeId
          );

        unset($imgs[$file]);

        continue;
      }

      if (!$idsToProcess = array_diff($ids, array_keys($_ids)))
        continue;

      $img = $data['img'];
      $val = array(
        'attribute_id' => $galleryAttributeId,
        'value' => $file
      );

      foreach ($idsToProcess as $id) {
        $val['entity_id'] = $id;
        $img['value_id'] = $resourse->insertGallery($val);
        $resourse->insertGalleryValueInStore($img);
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
