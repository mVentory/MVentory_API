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
 * Model for category matching rules
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Model_Matching
  extends Mage_Core_Model_Abstract
  implements IteratorAggregate {

  const DEFAULT_RULE_ID = 'mventory_default_rule';

  /**
   * Initialize resource mode
   *
   */
  protected function _construct () {
    $this->_init('mventory/matching');
  }

  public function loadBySetId ($setId, $cleanRules = true) {
    $this
      ->setData('attribute_set_id', $setId)
      ->load($setId, 'attribute_set_id');

    return $cleanRules ? $this->_clean() : $this;
  }

  public function getIterator () {
    return new ArrayIterator($this->getData('rules'));
  }

  public function append ($rule) {
    $all = $this->getData('rules');

    if ($rule['id'] != self::DEFAULT_RULE_ID
        && isset($all[self::DEFAULT_RULE_ID])) {
      $defaultRule = array_pop($all);

      $all[$rule['id']] = $rule;
      $all[self::DEFAULT_RULE_ID] = $defaultRule;
    } else
      $all[$rule['id']] = $rule;

    return $this->setData('rules', $all);
  }

  public function remove ($ruleId) {
    $all = $this->getData('rules');

    unset($all[$ruleId]);

    return $this->setData('rules', $all);
  }

  public function reorder ($ids) {
    $_all = $this->getData('rules');

    $all = array();

    foreach ($ids as $id)
      $all[$id] = $_all[$id];

    if (isset($_all[self::DEFAULT_RULE_ID]))
      $all[self::DEFAULT_RULE_ID] = $_all[self::DEFAULT_RULE_ID];

    return $this->setData('rules', $all);
  }

  public function matchCategory ($product) {
    if (($setId = $product->getAttributeSetId()) === false)
      return $this->_getLostCategoryId($product);

    $this->loadBySetId($setId);

    if (!$this->getId())
      return $this->_getLostCategoryId($product);

    $_attributes = array();

    $attributes = $product
      ->getTypeInstance()
      ->getEditableAttributes($product);

    foreach ($attributes as $code => $attribute)
      if ($id = $attribute->getId())
        $_attributes[$id] = $code;

    unset($attributes, $attribute, $code, $id);

    $categoryIds = array();

    $rules = $this->getData('rules');

    foreach ($rules as $rule) {
      foreach ($rule['attrs'] as $attribute) {
        if (!isset($_attributes[$attribute['id']]))
          continue;

        $code = $_attributes[$attribute['id']];

        $productValue = $product->getData($code);
        $productValue = is_string($productValue)
                          ? explode(',', $productValue)
                            : (array) $productValue;

        $ruleValue = (array) $attribute['value'];

        if (!count(array_intersect($productValue, $ruleValue)))
          continue 2;
      }

      if (!empty($rule['categories'])) {
          $categoryIds = $rule['categories'];
          break;
      }
    }

    if (!$categoryIds && isset($rules[self::DEFAULT_RULE_ID]))
      $categoryIds = $rules[self::DEFAULT_RULE_ID]['categories'];

    /**
     * @todo This should be loaded on demand in the next two if blocks
     */
    $categories = Mage::getResourceModel('catalog/category_collection')
                    ->load()
                    ->toArray();

    //Check if matched categories still exist; remove non-existing
    if ($categoryIds)
      foreach ($categoryIds as $i => $cid)
        if (!isset($categories[(int) $cid]))
          unset($categoryIds[$i]);

    //Use lost category (if it exists) when there's no matched categories
    if (!$categoryIds) {
      $lostCategoryId = $this->_getLostCategoryId($product);

      if ($lostCategoryId && isset($categories[$lostCategoryId[0]]))
        return $lostCategoryId;
    }

    return $categoryIds;
  }

  /**
   * Return value of Default category ID setting
   *
   * @param Mage_Catalog_Model_Product $product
   *   Product model to get product's website
   *
   * @return array
   *   List with one ID of default category or empty list if the setting
   *   is not set
   */
  protected function _getLostCategoryId ($product) {
    $helper = Mage::helper('mventory/product');

    $id = $helper->getConfig(
      MVentory_API_Model_Config::_LOST_CATEGORY,
      $helper->getWebsite($product)
    );

    return empty($id) ? array() : array((int) $id);
  }

  protected function _clean () {
    $attrs = array();

    $_attrs = Mage::getResourceModel('catalog/product_attribute_collection')
               ->setAttributeSetFilter($this->getData('attribute_set_id'));

    foreach ($_attrs as $attr) {
      if (!$attr->getIsUserDefined())
        continue;

      $type = $attr->getFrontendInput();

      if (!($type == 'select' || $type == 'multiselect'))
        continue;

      $allOptions = $attr
                      ->getSource()
                      ->getAllOptions();

      $optionIds = array();

      foreach ($allOptions as $options)
        if ($options['value'])
          $optionIds[] = $options['value'];

      $attrs[$attr->getId()] = $optionIds;
    }

    unset($_attrs);

    $rules = $this->getData('rules');

    $isChanged = false;

    foreach ($rules as $ruleId => &$rule) {
      if ($ruleId == self::DEFAULT_RULE_ID)
        continue;

      foreach ($rule['attrs'] as $n => $attr) {
        $id = $attr['id'];

        //Keep attribute in the rule if it exists in the system, has values
        //and containes one value at least which exists in the attribute
        $keepAttr = isset($attrs[$id])
                    && count($attrs[$id])
                    && count(array_intersect($attr['value'], $attrs[$id]));

        if (!$keepAttr) {
          unset($rule['attrs'][$n]);

          $isChanged = true;
        }
      }

      if (!count($rule['attrs'])) {
        unset($rules[$ruleId]);

        $isChanged = true;
      }
    }

    if ($isChanged)
      $this
        ->setData('rules', $rules)
        ->save();

    return $this;
  }
}
