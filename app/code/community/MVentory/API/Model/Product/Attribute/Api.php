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
 * Catalog product attribute api
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Model_Product_Attribute_Api
  extends Mage_Catalog_Model_Product_Attribute_Api {

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

  /**
   * Get information about attribute with list of options
   *
   * @param integer|string $attribute attribute ID or code
   * @return array
   */
  protected function _info ($attr) {
    $attr = $attr instanceof Mage_Catalog_Model_Resource_Eav_Attribute
              ? $attr
                : $this->_getAttribute($attr);

    $storeId = Mage::helper('mventory')->getCurrentStoreId();

    $label = (($labels = $attr->getStoreLabels()) && isset($labels[$storeId]))
               ? $labels[$storeId]
                 : $attr->getFrontendLabel();

    $frontendInput = $attr->getFrontendInput();

    $result = array(
      'attribute_id' => $attr->getId(),
      'attribute_code' => $attr->getAttributeCode(),
      'frontend_input' => $attr->getFrontendInput(),
      'default_value' => $attr->getDefaultValue(),
      'is_required' => $attr->getIsRequired(),
      'frontend_class' => (string) $attr->getFrontendClass(),
      'is_html_allowed_on_front' => $attr->getIsHtmlAllowedOnFront(),

      //Return value of is_configurable field only for attributes which can be
      //used to create configurable products (such as global with dropdown
      //as frontend input) and 0 for others, because Magento sets
      //is_configurable to 1 for all attributes by default on create.
      'is_configurable' => $attr->isScopeGlobal() && $frontendInput == 'select'
                             ? $attr->getIsConfigurable()
                               : '0',

      'label' => $label,

      //!!!DEPRECATED: replaced by 'label' key
      //!!!TODO: remove after the app will have been upgraded
      'frontend_label' => array(
        array('store_id' => 0, 'label' => $label)
      ),

      'options' => $this->optionsPerStoreView($attr->getId(), $storeId)
    );

    return $result + $this->_prepareMetadata($attr);
  }

  public function fullInfoList ($setId) {

    //Add 'cost' attr to the list of ignored attrs here instead in constructure
    //to not affect other core API calls
    $this->_ignoredAttributeCodes[] = 'cost';

    $attrs = Mage::getModel('catalog/product')
      ->getResource()
      ->loadAllAttributes()
      ->getSortedAttributes($setId);

    $result = array();

    foreach ($attrs as $attr)
      if ((!$attr->getId() || $attr->isInSet($setId))
          && $this->__isAllowedAttribute($attr))
        $result[] = $this->_info($attr);

    return $result;
  }

  public function addOptionAndReturnInfo ($attribute, $value) {
    $storeId = Mage::helper('mventory')->getCurrentStoreId();

    $attribute = $this->_getAttribute($attribute);
    $attributeId = $attribute->getId();

    $options = Mage::getResourceModel('eav/entity_attribute_option_collection')
                 ->setAttributeFilter($attributeId)
                 ->setStoreFilter($storeId);

    $_value = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $value));

    $hasOption = false;

    foreach ($options as $option) {
      $def = $option->getDefaultValue();
      $val = $option->getValue();

      if ($_value == strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $def))) {
        if ($val == '~')
          $this->_removeOptionValue($option->getId(), $storeId);

        $hasOption = true;

        break;
      }

      if ($def != $val
          && $val != '~'
          && $_value == strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $val))) {

        $hasOption = true;

        break;
      }
    }

    if (!$hasOption) {
      try {
        $data = array(
          'label' => array(
            array(
              'store_id' => 0,
              'value' => $value
            )
          ),

          'order' => 0,
          'is_default' => false
        );

        $this->addOption($attributeId, $data);

        $helper = Mage::helper('mventory');

        $subject = 'New attribute value: ' . $value;
        $body = $subject;

        if ($customer = $helper->getCustomerByApiUser())
          $body .= "\n\n"
                   . 'Attribute code: ' . $attribute->getAttributeCode() . "\n"
                   . 'Customer ID: ' . $customer->getId() . "\n"
                   . 'Customer e-mail: ' . $customer->getEmail();

        $helper->sendEmail($subject, $body);
      } catch (Exception $e) {}
    }

    return $helper->prepareApiResponse($this->_info($attributeId));
  }

  private function getOptionLabels($storeId, $attributeId)
  {
    $values = array();

    $valuesCollection = Mage::getResourceModel('eav/entity_attribute_option_collection')
      ->setAttributeFilter($attributeId)
      ->setStoreFilter($storeId, false)
      ->load();

    foreach ($valuesCollection as $item) {
      $values[$item->getId()] = $item->getValue();
    }

    return $values;
  }

  private function optionsPerStoreView($attribute, $storeId)
  {
    $attributeModel = Mage::getResourceModel('catalog/eav_attribute')
      ->setEntityTypeId(Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId());

    if (is_numeric($attribute)) {
      $attributeModel->load(intval($attribute));
    } else {
      $attributeModel->load($attribute, 'attribute_code');
    }

    $attributeId = $attributeModel->getId();

    if (!$attributeId) {
      $this->_fault('not_exists');
    }

    $defaultOptionLabels = $this->getOptionLabels(0, $attributeId);
    $optionLabels = $this->getOptionLabels($storeId, $attributeId);

    $optionsCollection = Mage::getResourceModel('eav/entity_attribute_option_collection')
      ->setAttributeFilter($attributeId)
      ->setPositionOrder('desc', true)
      ->load();

    $values = array();

    foreach ($optionsCollection as $option)
    {
      $optionLabel = '~';

      if (isset($optionLabels[$option->getId()])) {
        $optionLabel = $optionLabels[$option->getId()];
      } elseif (isset($defaultOptionLabels[$option->getId()])) {
        $optionLabel = $defaultOptionLabels[$option->getId()];
      }

      if (!is_null($optionLabel) && strcmp($optionLabel, '~') != 0) {
        $values[] = array('value' => $option->getId(),
                          'label' => $optionLabel);
      }
    }
    return $values;
  }

  protected function _removeOptionValue ($optionId, $storeId) {
    $resource = Mage::getSingleton('core/resource');

    $table = $resource->getTableName('eav/attribute_option_value');

    $condition = array(
      'option_id = ?' => $optionId,
      'store_id = ?' => $storeId
    );

    return $resource
             ->getConnection('core_write')
             ->delete($table, $condition);
  }

  protected function __isAllowedAttribute ($attr, $attrs = null) {
    if (!parent::_isAllowedAttribute($attr, $attrs))
      return false;

    if (!(($attr->getIsVisible() && $attr->getIsUserDefined())
          || isset($this->_whitelist[$attr->getAttributeCode()])))
      return false;

    $storeId = Mage::helper('mventory')->getCurrentStoreId();

    $label = (($labels = $attr->getStoreLabels()) && isset($labels[$storeId]))
               ? $labels[$storeId]
                 : $attr->getFrontendLabel();

    return $label != '~';
  }

  /**
   * Deserialise and return metadata of it's available otherwise return
   * empty array
   *
   * @param array $attr Attr data
   * @return array Deserialised metadata
   */
  protected function _getMetadata ($attr) {
    if (!$attr['mventory_metadata'])
      return array();

    return ($metadata = unserialize($attr['mventory_metadata'])) === false
             ? array()
               : $metadata;
  }

  /**
   * Set default values for metadata fields if they don't have value in the
   * attr's metadata
   *
   * @param array $attr Attr data
   * @return array Prepared metadata
   */
  protected function _prepareMetadata ($attr) {
    $metadata = $this->_getMetadata ($attr);
    $defaults = (array) Mage::getConfig()->getNode(
      'mventory/metadata',
      'default'
    );

    //Remove following fields from output of API
    unset(
      $metadata['invisible_for_websites'],
      $defaults['invisible_for_websites']
    );

    foreach ($defaults as $field => $defValue)
      if (!isset($metadata[$field]))
        $metadata[$field] = (string) $defValue;

    return $metadata;
  }
}
