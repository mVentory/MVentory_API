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
 * Source model for 'Default input method' and 'Alternative input methods'
 * options in the attribute metadata for the app
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Model_System_Config_Source_Inputmethod
{
  /**
   * Options getter
   *
   * @return array
   */
  public function toOptionArray () {
    $helper = Mage::helper('mventory');

    return array(
      array(
        'value' => MVentory_API_Model_Config::MT_INPUT_KBD,
        'label' => $helper->__('Normal keyboard')
      ),
      array(
        'value' => MVentory_API_Model_Config::MT_INPUT_NUMKBD,
        'label' => $helper->__('Numeric keyboard')
      ),
      array(
        'value' => MVentory_API_Model_Config::MT_INPUT_SCANNER,
        'label' => $helper->__('Scanner')
      ),
      array(
        'value' => MVentory_API_Model_Config::MT_INPUT_GESTURES,
        'label' => $helper->__('Gestures (hand-writing)')
      ),
      array(
        'value' => MVentory_API_Model_Config::MT_INPUT_INTERNETSEARCH,
        'label' => $helper->__('Copy from internet search')
      ),
      array(
        'value' => MVentory_API_Model_Config::MT_INPUT_ANOTHERPROD,
        'label' => $helper->__('Copy from another product')
      )
    );
  }
}

?>
