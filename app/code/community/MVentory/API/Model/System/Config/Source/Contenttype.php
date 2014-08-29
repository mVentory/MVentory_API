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
      array('value' => 0, 'label' => $helper->__('None')),
      array('value' => 1, 'label' => $helper->__('Video')),
      array('value' => 2, 'label' => $helper->__('Image')),
      array('value' => 3, 'label' => $helper->__('Web page')),
      array('value' => 4, 'label' => $helper->__('ISBN10')),
      array('value' => 5, 'label' => $helper->__('ISBN13')),
      array('value' => 6, 'label' => $helper->__('ISSN')),
      array('value' => 7, 'label' => $helper->__('Secondary barcode block'))
    );
  }
}

?>
