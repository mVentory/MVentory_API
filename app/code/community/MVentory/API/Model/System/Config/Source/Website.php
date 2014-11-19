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
 * Source model for 'Make invisible for' metadata field.
 * Extended to add Visible in all option
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */

class MVentory_API_Model_System_Config_Source_Website
  extends Mage_Adminhtml_Model_System_Config_Source_Website
{

  /**
   * Options getter
   *
   * @return array
   */
  public function toOptionArray () {
    if (!$this->_options) {
      parent::toOptionArray();

      array_unshift(
        $this->_options,
        array(
          'value' => '',
          'label' => Mage::helper('mventory')->__('Visible in all')
        )
      );
    }

    return $this->_options;
  }
}
