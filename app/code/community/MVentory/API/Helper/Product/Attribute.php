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
 * Product attribute helper
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Helper_Product_Attribute
  extends MVentory_API_Helper_Product
{
  protected $_whitelist = array(
    'category_ids' => true,
    'name' => true,
    'description' => true,
    'short_description' => true,
    'sku' => true,
    'price' => true,
    'special_price' => true,
    'special_from_date' => true,
    'special_to_data' => true,
    'weight' => true,
    'tax_class_id' => true
  );

  protected $_blacklist = array('cost' => true);

  protected $_nonReplicable = array(
    'sku' => true,
    'price' => true,
    'special_price' => true,
    'special_from_date' => true,
    'special_to_data' => true,
    'weight' => true,
    'product_barcode_' => true,
  );

  /**
   * List of attributes which use special functions to set/get values
   */
  protected $_attrsSetGet = array(
    'category_ids' => array(
      'set' => 'setCategoryIds',
      'get' => 'getCategoryIds',
      'cmp' => 'array_diff'
    )
  );

  public function getAttrsSetGetInfo () {
    return $this->_attrsSetGet;
  }

  public function getEditables ($setId) {
    $attrs = array();

    foreach ($this->_getAttrs($setId) as $attr)
      if ((!$attr->getId() || $attr->isInSet($setId))
          && $this->_isAllowedAttribute($attr))
        $attrs[$attr->getAttributeCode()] = $attr;

    return $attrs;
  }

  public function getReplicables ($setId, $ignore = array()) {
    $attrs = array();

    $ignore = $this->_nonReplicable + $ignore;

    foreach ($this->_getAttrs($setId) as $attr)
      if ((!$attr->getId() || $attr->isInSet($setId))
          && $this->_isAllowedAttribute($attr, $ignore)) {
        $code = $attr->getAttributeCode();
        $attrs[$code] = $code;
      }

    return $attrs;
  }

  protected function _getAttrs ($setId) {
    return Mage::getModel('catalog/product')
      ->getResource()
      ->loadAllAttributes()
      ->getSortedAttributes($setId);
  }

  protected function _isAllowedAttribute ($attr, $ignore = array()) {
    $code = $attr->getAttributeCode();

    if (isset($ignore[$code]) || isset($this->_blacklist[$code]))
      return false;

    if (!(($attr->getIsVisible() && $attr->getIsUserDefined())
          || isset($this->_whitelist[$code])))
      return false;

    //!!!TODO: replace with filtering by metadata option
    $storeId = Mage::helper('mventory')->getCurrentStoreId();

    $label = (($labels = $attr->getStoreLabels()) && isset($labels[$storeId]))
               ? $labels[$storeId]
                 : $attr->getFrontendLabel();

    return $label != '~';
  }
}
