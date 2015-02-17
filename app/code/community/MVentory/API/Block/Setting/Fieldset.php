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
 * Config form fieldset renderer
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Block_Setting_Fieldset
  extends Mage_Adminhtml_Block_System_Config_Form_Fieldset
{

  /**
   * Return value of field that is set in the related XML node
   *
   * @param string $key Name of field
   * @return string|null
   */
  public function getXmlData ($key) {
    $data = $this
      ->getElement()
      ->getOriginalData();

    return isset($data[$key]) ? $data[$key] : null;
  }

  /**
   * Return footer html for fieldset
   * Add extra tooltip comments to elements
   *
   * @param Varien_Data_Form_Element_Abstract $element
   * @return string
   */
  protected function _getFooterHtml ($element) {
    $html = '</tbody></table>';

    if ($footer = $this->getXmlData('footer'))
      $html .= '<div class="fieldset-footer">' . $footer . '</div>';

    return $html
           . '</fieldset>'
           . $this->_getExtraJs($element, false)
           . '</div>'
           . ($element->getIsNested() ? '</td></tr>' : '');
  }
}

?>