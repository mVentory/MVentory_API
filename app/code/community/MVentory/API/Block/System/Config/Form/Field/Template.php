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
 * @copyright Copyright (c) 2015 mVentory Ltd. (http://mventory.com)
 * @license http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

/**
 * Template field for fieldset
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Block_System_Config_Form_Field_Template
  extends Mage_Adminhtml_Block_Template
  implements Varien_Data_Form_Element_Renderer_Interface
{
  const _TPL_ELEMENT = <<<'EOT'
<tr class="system-fieldset-sub-head mventory-field-template" id="row_%s">
  <td colspan="5">%s</td>
</tr>
EOT;

  /**
   * Render element html
   *
   * @param Varien_Data_Form_Element_Abstract $element
   * @return string
   */
  public function render (Varien_Data_Form_Element_Abstract $element) {
    return sprintf(
      self::_TPL_ELEMENT,
      $element->getHtmlId(),
      $this
        ->setTemplate($element->getOriginalData('template'))
        ->toHtml()
    );
  }
}
