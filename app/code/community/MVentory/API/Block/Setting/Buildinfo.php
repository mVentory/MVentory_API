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
 * Block for displaying build version in system config
 *
 * @package MVentory/API
 * @author Andrew Gilman <andrew@mventory.com>
 */

class MVentory_API_Block_Setting_Buildinfo
  extends Mage_Adminhtml_Block_Abstract
  implements Varien_Data_Form_Element_Renderer_Interface
{
  protected $_template = 'mventory/config/build-info.phtml';

  /**
   * Render fieldset html
   *
   * @param Varien_Data_Form_Element_Abstract $element
   * @return string
   */
  public function render (Varien_Data_Form_Element_Abstract $element) {
    return $this->toHtml();
  }
}

