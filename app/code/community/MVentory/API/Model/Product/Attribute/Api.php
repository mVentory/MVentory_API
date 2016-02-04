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
 * Catalog product attribute api
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Model_Product_Attribute_Api
  extends Mage_Catalog_Model_Product_Attribute_Api {

  /**
   * Helper class
   *
   * @var MVentory_API_Helper_Product_Attribute
   */
  protected $_helper;

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
   * Constructor
   */
  public function __construct () {
    $this->_helper = Mage::helper('mventory/product_attribute');
  }

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

    $storeId = $this->_helper->getCurrentStoreId();

    $label = (($labels = $attr->getStoreLabels()) && isset($labels[$storeId]))
               ? $labels[$storeId]
                 : $attr->getFrontendLabel();

    $frontendInput = $attr->getFrontendInput();

    $result = array(
      'attribute_id' => $attr->getId(),
      'attribute_code' => $attr->getAttributeCode(),
      'frontend_input' => $attr->getFrontendInput(),
      'default_value' => (string) $attr->getDefaultValue(),
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

      'options' => $this->_getOptions($attr->setStoreId($storeId))
    );

    //!!!TODO: remove when not needed
    //Temporarily set 'category_ids' attribite to read-only until we will
    //find final solution for 'category_ids'
    $metadata = $this->_prepareMetadata(
      $this->_metadataHelper->get($attr)
    );

    if ($result['attribute_code'] == 'category_ids')
      $metadata['readonly'] = '1';

    return $result + $metadata;
  }

  public function fullInfoList ($setId) {
    $this->_metadataHelper = Mage::helper('mventory/metadata');

    $result = array();
    $attrs = $this->_helper->getEditables($setId);

    foreach ($attrs as $attr)
      $result[] = $this->_info($attr->getAttributeId());

    return $result;
  }

  public function addOptionAndReturnInfo ($attribute, $value) {
    $storeId = $this->_helper->getCurrentStoreId();

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

        $subject = 'New attribute value: ' . $value;
        $body = 'Attribute code: ' . $attribute->getAttributeCode() . "\n"
                . $subject;

        $apiUser = $this->_helper->getApiUser();
        if ($apiUser)
          $body .= "\n\n"
                   . 'API user ID: ' . $apiUser->getId() . "\n"
                   . 'API user e-mail: ' . $apiUser->getEmail();

        $this->_helper->sendEmail($subject, $body);
      } catch (Exception $e) {}
    }

    return $this->_helper->prepareApiResponse($this->_info($attributeId));
  }

  /**
   * Return options for supplied attribute, filter out all disabled/hidden
   * options (i.e. with '~' as a label)
   *
   * @param Mage_Catalog_Model_Resource_Eav_Attribute $attr
   *   Instance of attribute model
   *
   * @return array
   *   List of prepared options
   */
  public function _getOptions ($attr) {
    try {
      $source = $attr->getSource();
    }
    catch (Exception $e) {
      return [];
    }

    $canGetOptions = $source
                     && is_object($source)
                     && method_exists($source, 'getAllOptions');

    if (!$canGetOptions)
      return [];

    $options = [];

    try {
      //Filter out options having '~' as a label
      foreach ($source->getAllOptions(false) as $option)
        if ($option['label'] !== '~')
          $options[] = $option;
    }
    catch (Exception $e) {}

    return $options;
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

  /**
   * Set default values for metadata fields if they don't have value in the
   * attr's metadata
   *
   * @param array $metadata Parsed metadata from attribute
   * @return array Prepared metadata
   */
  protected function _prepareMetadata ($metadata) {
    $defaults = (array) Mage::getConfig()->getNode(
      'mventory/metadata',
      'default'
    );

    //Remove following fields from output of API
    unset(
      $metadata['invisible_for_websites'],
      $defaults['invisible_for_websites']
    );

    foreach ($defaults as $field => $defValue) {
      if (!isset($metadata[$field])) {
        $metadata[$field] = (string) $defValue;

        continue;
      }

      //Convert metadata value to string, array to comma-separated list
      $metadata[$field] = is_array($value = $metadata[$field])
        ? implode(',', $value)
          : (string) $value;
    }

    return $metadata;
  }

  /**
   * Load model by attribute ID or code
   *
   * This method is redefined to convert not_exists fault
   * to attribute_not_exists to avoid conflictc with similar faults
   * from other modules
   *
   * @param int|string $attribute
   *   Attribute ID or code
   *
   * @return Mage_Catalog_Model_Resource_Eav_Attribute
   *   Instance of attribute model
   */
  protected function _getAttribute ($attribute) {
    try {
      return parent::_getAttribute($attribute);
    }
    catch (Mage_Api_Exception $e) {
      throw new Mage_Api_Exception('attribute_not_exists');
    }
  }
}
