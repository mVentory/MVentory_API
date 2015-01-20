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
 * Image review logging field
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Block_System_Config_Form_Field_Imgcliplog
  extends Mage_Adminhtml_Block_System_Config_Form_Field
{

  const _JS_SET_LOCATION = <<<'EOT'
javascript:setLocation('%s'); return false;
EOT;

  const _JS_CONF_SET_LOCATION = <<<'EOT'
javascript:confirmSetLocation('%s', '%s'); return false;
EOT;

  /**
   * Return element html
   *
   * @param Varien_Data_Form_Element_Abstract $element
   *   Form element
   *
   * @return string
   *   Element HTML
   */
  protected function _getElementHtml (Varien_Data_Form_Element_Abstract $element) {
    return '<div class="buttons-set">'
             . $this->_getDownloadButton()
             . $this->_getClearButton()
           . '</div>';
  }

  /**
   * Return HTML for Download button
   *
   * @return string
   *   HTML of the Download button
   */
  protected function _getDownloadButton () {
    $url = $this->getUrl('mventory/logs/download');

    return $this
      ->getLayout()
      ->createBlock('adminhtml/widget_button')
      ->setData(array(
          'label' => $this->__('Download'),
          'title' => $this->__('Download log file'),
          'class' => 'go',
          'onclick' => sprintf(self::_JS_SET_LOCATION, $url)
        ))
      ->toHtml();
  }

  /**
   * Return HTML for Clear button
   *
   * @return string
   *   HTML of the Clear button
   */
  protected function _getClearButton () {
    $url = $this->getUrl('mventory/logs/clear');
    $msg = $this->__('All current data in the activity log will be erased');

    return $this
      ->getLayout()
      ->createBlock('adminhtml/widget_button')
      ->setData(array(
          'label' => $this->__('Clear'),
          'title' => $this->__('Clear log file'),
          'class' => 'delete',
          'onclick' => sprintf(self::_JS_CONF_SET_LOCATION, $msg, $url)
        ))
      ->toHtml();
  }
}
