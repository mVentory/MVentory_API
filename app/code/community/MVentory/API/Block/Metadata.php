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
 * Attribute metadata tab
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */

class MVentory_API_Block_Metadata
  extends Mage_Adminhtml_Block_Widget_Form
  implements Mage_Adminhtml_Block_Widget_Tab_Interface
{

  /**
   * Return Tab label
   *
   * @return string
   */
  public function getTabLabel() {
    return $this->__('mVentory Properties');
  }

  /**
   * Return Tab title
   *
   * @return string
   */
  public function getTabTitle() {
    return $this->__('mVentory Properties');
  }

  /**
   * Can show tab in tabs
   *
   * @return boolean
   */
  public function canShowTab() {
    return true;
  }

  /**
   * Tab is hidden
   *
   * @return boolean
   */
  public function isHidden() {
    return false;
  }

  /**
   * Prepare form
   *
   * @return MVentory_API_Block_Metadata
   */
  protected function _prepareForm () {
    $attr = Mage::registry('entity_attribute');
    $frontendInput = $attr->getFrontendInput();

    //Load default values for settings
    $defaults = (array) Mage::getConfig()->getNode(
      'mventory/metadata',
      'default'
    );

    //Settings value are saved as serialised in single field
    $values = unserialize($attr['mventory_metadata']);

    $form = new Varien_Data_Form(array(
      'id' => 'edit_form',
      'action' => $this->getData('action'),
      'method' => 'post'
    ));

    //Initiate element renderer block to set modified template for it.
    //We added support for tooltips to form elements as in System/Config section
    //in the Admin interface
    $elementRenderer = $this
      ->getLayout()
      ->createBlock('adminhtml/widget_form_renderer_fieldset_element')
      ->setTemplate('mventory/element.phtml');

    $fieldset = $form->addFieldset(
      'mventory_fieldset',
      array('legend' => $this->__('mVentory App Properties'))
    );

    //Load settings described in config.xml file
    $fields = (array) Mage::getConfig()->getNode('mventory/metadata/fields');

    foreach ($fields as $id => $field) {
      if ($field->depends) {
        $depends = (string) $field->depends;

        if ($depends && !in_array($frontendInput, explode(',', $depends)))
          continue;
      }

      $type = (string) $field->type;

      $config = array(
        'name' => 'mventory_metadata[' . $id . ']',
        'label' => $this->__((string) $field->label)
      );

      if ($note = (string) $field->note)
        $config['note'] = $this->__($note);

      if ($tooltip = (string) $field->tooltip)
        $config['tooltip'] = $this->__($tooltip);

      if ($source = (string) $field->source_model)
        $config['values'] = $this->_getOptions($source, $type);

      $config['value'] = $this->_getValue($id, $values, $defaults);

      $fieldset
        ->addField($id, $type, $config)
        ->setRenderer($elementRenderer);
    }

    $this->setForm($form);

    return parent::_prepareForm();
  }

  /**
   * Parse value of <source_model> tag from setting description,
   * initiate source model and fetch options
   *
   * Value of the tag has following format: module/source_model[::method].
   * Method part is optional. Default method is "toOptionArray($type)"
   *
   * @param string $factoryName Value of <source_model> tag
   * @param string $type Type of attr's frontend input (text, select, etc...)
   * @return array List of options
   */
  protected function _getOptions ($factoryName, $type) {
    $method = false;

    if (preg_match('/^([^:]+?)::([^:]+?)$/', $factoryName, $matches)) {
      array_shift($matches);
      list($factoryName, $method) = array_values($matches);
    }

    $source = Mage::getSingleton($factoryName);

    if (!$method)
      return $source->toOptionArray($type == 'multiselect');

    if ($type == 'multiselect')
      return $source->$method();

    $options = array();

    foreach ($source->$method() as $value => $label)
      $options[] = array('label' => $label, 'value' => $value);

    return $options;
  }

  /**
   * Return value for the specified setting or its defaullt value if it's set
   *
   * @param string $field Name of setting
   * @param array $values List of values stored in the attribute
   * @param array $defaults List of default values
   * @return number|string|bool|null
   */
  protected function _getValue ($field, $values, $defaults) {
    if (isset($values[$field]))
      return $values[$field];

    if (isset($defaults[$field]))
      return $defaults[$field];

    return null;
  }
}
