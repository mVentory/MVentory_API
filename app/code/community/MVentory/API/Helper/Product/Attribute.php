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
    'special_to_date' => true,
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
    //Save ID of current website to use later (e.g. in _isAllowedAttribute())
    $this->_websiteId = $this->getCurrentWebsite()->getId();

    $attrs = array();

    foreach ($this->_getAttrs($setId) as $attr)
      if ((!$attr->getId() || $attr->isInSet($setId))
          && $this->_isAllowedAttribute($attr))
        $attrs[$attr->getAttributeCode()] = $attr;

    return $attrs;
  }

  public function getReplicables ($setId, $ignore = array()) {
    //Save ID of current website to use later (e.g. in _isAllowedAttribute())
    $this->_websiteId = $this->getCurrentWebsite()->getId();

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

  public function getWritables ($setId) {
    //Save ID of current website to use later (e.g. in _isAllowedAttribute())
    $this->_websiteId = $this->getCurrentWebsite()->getId();

    $attrs = array();

    foreach ($this->_getAttrs($setId) as $attr)
      if ((!$attr->getId() || $attr->isInSet($setId))
          && $this->_isAllowedAttribute($attr)
          && !(($metadata = $attr['mventory_metadata'])
               && isset($metadata['readonly'])
               && (1 == (int) $metadata['readonly'])))
        $attrs[$attr->getAttributeCode()] = $attr;

    return $attrs;
  }

  /**
   * Return configurable attribute by attribute set ID
   *
   * It returns first configurable attribute which is global
   * and has select as frontend
   *
   * NOTE: the function returns first attribute because we support
   *       only one configurable attribute in product
   *
   * @param int $setId Attribute set ID
   * @return Mage_Eav_Model_Entity_Attribute Configurable attribute
   */
  public function getConfigurable ($setId) {
    //Save ID of current website to use later (e.g. in _isAllowedAttribute())
    $this->_websiteId = $this->getCurrentWebsite()->getId();

    foreach ($this->_getAttrs($setId) as $attr)
      if ((!$attr->getId() || $attr->isInSet($setId))
          && $attr->isScopeGlobal()
          && ($attr->getFrontendInput() == 'select')
          && ($attr->getIsConfigurable() == '1')
          && $this->_isAllowedAttribute($attr))
      return $attr;
  }

  /**
   * Deserialise and set metadata in the attribute if it's available
   *
   * @param Mage_Eav_Model_Entity_Attribute $attr Attribute
   * @return array Deserialised metadata
   */
  public function parseMetadata ($attr) {
    if (is_array($raw = $attr['mventory_metadata']))
      return $raw;

    $attr['mventory_metadata']
      = ($raw && ($metadata = unserialize($raw)) !== false)
        ? $metadata
          : ($metadata = array());

    return $metadata;
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

    return !(($metadata = $this->parseMetadata($attr))
             && isset($metadata['invisible_for_websites'])
             && ($websites = $metadata['invisible_for_websites'])
             && in_array(
                  $this->_websiteId,
                  is_array($websites) ? $websites : explode(',', $websites)
                )
            );
  }
}
