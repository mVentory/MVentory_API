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
 * Source model for special content type in the app
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Model_System_Config_Source_Contenttype
{
  /**
   * Options getter
   *
   * @return array
   */
  public function toOptionArray () {
    $helper = Mage::helper('mventory');

    return array(
      array('value' => 0, 'label' => $helper->__('Text')),
      array('value' => 1, 'label' => $helper->__('YouTube video ID')),
      array('value' => 2, 'label' => $helper->__('Web address')),
      array('value' => 3, 'label' => $helper->__('ISBN10')),
      array('value' => 4, 'label' => $helper->__('ISBN13')),
      array('value' => 5, 'label' => $helper->__('Secondary barcode block')),
      array('value' => 6, 'label' => $helper->__('ISSN'))
    );
  }
}

?>
