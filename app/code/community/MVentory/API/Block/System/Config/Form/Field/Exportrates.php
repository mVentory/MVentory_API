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
 * Button for exporting rates in CSV format for the volume based shipping
 * carrier
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Block_System_Config_Form_Field_Exportrates
  extends Mage_Adminhtml_Block_System_Config_Form_Field {

  protected function _getElementHtml (Varien_Data_Form_Element_Abstract $elem) {
    $website = $this
                 ->getRequest()
                 ->getParam('website', '');

    $url = $this->getUrl(
      'adminhtml/mventory_carriers/export',
      compact('website')
     );

    $data = array(
      'label' => $this->__('Export CSV'),
      'onclick' => 'setLocation(\'' . $url . '\')'
    );

    return $this
             ->getLayout()
             ->createBlock('adminhtml/widget_button')
             ->setData($data)
             ->toHtml();
  }
}
