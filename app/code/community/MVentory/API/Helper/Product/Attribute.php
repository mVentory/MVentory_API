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
 * Product attribute helper
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Helper_Product_Attribute
  extends MVentory_API_Helper_Product
{
  protected $_blacklist = array('cost' => true);

  /**
   * Non-replicable user defined attributes
   *
   * @var array
   */
  protected $_nonReplicable = [
    'product_barcode_' => true,
  ];

  /**
   * Replicable system attributes, the rest of system attributes is ignored
   *
   * @var array
   */
  protected $_replicable = [
    'category_ids' => true,
    'name' => true,
    'description' => true,
    'short_description' => true,
    'tax_class_id' => true
  ];

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
    //Load helper which is used in _isAllowedAttribute() method
    $this->_metadataHelper = Mage::helper('mventory/metadata');

    $attrs = array();

    foreach ($this->_getAttrs($setId) as $attr)
      if ($this->_isAllowedAttribute($attr))
        $attrs[$attr->getAttributeCode()] = $attr;

    return $attrs;
  }

  public function getReplicables ($setId, $ignore = array()) {
    //Load helper which is used in _isAllowedAttribute() method
    $this->_metadataHelper = Mage::helper('mventory/metadata');

    $attrs = array();

    $ignore = $this->_nonReplicable + $ignore;

    foreach ($this->_getAttrs($setId) as $attr) {
      $code = $attr->getAttributeCode();

      if (isset($this->_replicable[$code])
          || $this->_isAllowedAttribute($attr, $ignore))
        $attrs[$code] = $code;
    }

    return $attrs;
  }

  public function getWritables ($setId) {
    //Load helper which is used in _isAllowedAttribute() method
    $this->_metadataHelper = Mage::helper('mventory/metadata');

    $attrs = array();

    foreach ($this->_getAttrs($setId) as $attr)
      if ($this->_isAllowedAttribute($attr)
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
    //Load helper which is used in _isAllowedAttribute() method
    $this->_metadataHelper = Mage::helper('mventory/metadata');

    foreach ($this->_getAttrs($setId) as $attr)
      if ($attr->isScopeGlobal()
          && ($attr->getFrontendInput() == 'select')
          && ($attr->getIsConfigurable() == '1')
          && $this->_isAllowedAttribute($attr))
      return $attr;
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

    try {
      return (bool) $this->_metadataHelper->get($attr, 'is_visible');
    }
    catch (OutOfBoundsException $e) {}

    //Allow user defined attributes and disallow system attributes
    //if metadata can't be loaded
    return $attr->getIsUserDefined() ?: false;
  }
}
