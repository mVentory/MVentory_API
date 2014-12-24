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
 * Block for list of category matching rules
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Block_Matching extends Mage_Adminhtml_Block_Template {

  protected $_attrs = null;
  protected $_categories = null;

  protected function _construct () {
    $this->_attrs['-1'] = array(
      'label' => $this->__('Select an attribute...'),
      'used' => false,
      'used_values' => array()
    );

    $labels[] = '';

    $attrs = Mage::getResourceModel('catalog/product_attribute_collection')
               ->setAttributeSetFilter($this->_getSetId());

    foreach ($attrs as $attr) {
      if (!$attr->getIsUserDefined())
        continue;

      $type = $attr->getFrontendInput();

      if (!($type == 'select' || $type == 'multiselect'))
        continue;

      $id = $attr->getId();

      $this->_attrs[$id] = array(
        'label' => $attr->getFrontendLabel(),
        'used' => false,
        'used_values' => array()
      );

      $labels[] = $attr->getFrontendLabel();
    }

    unset($attrs);

    $keys = array_keys($this->_attrs);

    array_multisort($labels, SORT_ASC, SORT_STRING, $keys, $this->_attrs);

    $this->_attrs = array_combine($keys, $this->_attrs);

    unset($keys, $labels);

    $options = Mage::getResourceModel('eav/entity_attribute_option_collection')
                 ->setAttributeFilter(array('in' => array_keys($this->_attrs)))
                 ->setStoreFilter($attr->getStoreId())
                 ->setPositionOrder('asc', true);

    foreach ($options as $option)
      $this->_attrs[$option->getAttributeId()]['values'][$option->getId()]
        = $option->getValue();

    $this->_categories = Mage::getResourceModel('catalog/category_collection')
                           ->addNameToResult()
                           ->load()
                           ->toArray();
  }

  /**
   * Prepare layout
   *
   * @return MVentory_API_Block_Matching
   */
  protected function _prepareLayout () {
    parent::_prepareLayout();

    $data = array(
      'id' => 'mventory-rule-reset',
      'label' => Mage::helper('mventory')->__('Reset rule')
    );

    $button = $this
                ->getLayout()
                ->createBlock('adminhtml/widget_button')
                ->setData($data);

    $this->setChild('button_rule_reset', $button);

    $data = array(
      'id' => 'mventory-rule-save',
      'class' => 'disabled',
      'label' => Mage::helper('mventory')->__('Save rule')
    );

    $button = $this
                ->getLayout()
                ->createBlock('adminhtml/widget_button')
                ->setData($data);

    $this->setChild('button_rule_save', $button);

    return $this;
  }

  protected function _getAttributesJson () {
    return Mage::helper('core')->jsonEncode($this->_attrs);
  }

  protected function _getRules () {
    return Mage::getModel('mventory/matching')
      ->loadBySetId($this->_getSetId());
  }

  protected function _getUrlsJson () {
    $params = array(
      'set_id' => $this->_getSetId(),
      'ajax' => true
    );

    $addrule = $this->getUrl('mventory/matching/append/', $params);
    $remove = $this->getUrl('mventory/matching/remove/', $params);
    $reorder = $this->getUrl('mventory/matching/reorder/', $params);

    return Mage::helper('core')->jsonEncode(compact('addrule',
                                                    'remove',
                                                    'reorder'));
  }

  /**
   * Retrieve current attribute set
   *
   * @return Mage_Eav_Model_Entity_Attribute_Set
   */
  protected function _getAttributeSet () {
    return Mage::registry('current_attribute_set');
  }

  /**
   * Retrieve current attribute set ID
   *
   * @return int
   */
  protected function _getSetId () {
    return $this->_getAttributeSet()->getId();
  }

  protected function _prepareRule ($data) {
    $id = $data['id'];
    $default = ($id == MVentory_API_Model_Matching::DEFAULT_RULE_ID);

    $attrs = array();

    foreach ($data['attrs'] as $attr) {

      //Ignore attribute which doesn't exists in Magento
      if (!isset($this->_attrs[$attr['id']]))
        continue;

      $_attr = &$this->_attrs[$attr['id']];

      $_attr['used'] = true;

      if (is_array($attr['value'])) {
        $values = array();

        foreach ($attr['value'] as $valueId)
          if (isset($_attr['values'][$valueId])) {
            $values[] = $_attr['values'][$valueId];

            $_attr['used_values'][$valueId] = true;
          }

        $attrs[$_attr['label']] = implode(', ', $values);

        continue;
      }

      $attrs[$_attr['label']] = isset($_attr['values'][$attr['value']])
                                  ? $_attr['values'][$attr['value']]
                                    : '';
    }

    return $this->_getRuleCategories($data)
           + array(
               'id' => $id,
               'default' => $default,
               'attrs' => $attrs
             );
  }

  protected function _getRuleCategories ($rule) {
    if (empty($rule['categories']))
      return array(
        'has_categories' => false,
        'categories' => array($this->__('Categories not selected'))
      );

    $categories = $rule['categories'];

    foreach ($categories as $i => $id)
      if (isset($this->_categories[$id]))
        $categories[$i] = $this->_categories[$id]['name'];
      else
        unset($categories[$i]);

    if (!$categories)
      return array(
        'has_categories' => false,
        'categories' => array($this->__('All categories don\'t exist anymore'))
      );

    return array(
        'has_categories' => true,
        'categories' => $categories
      );
  }
}
