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
 * @copyright Copyright (c) 2014-2015 mVentory Ltd. (http://mventory.com)
 * @license http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

/**
 * Backend model for Dropbox web folder setting
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Model_Setting_Backend_Dbxpath
  extends Mage_Core_Model_Config_Data
{
  /**
   * Processing setting data before save.
   * Trim setting value and surround it with slash symbols
   *
   * @return MVentory_API_Model_Setting_Backend_Dbxpath
   *   Instance of this class
   */
  public function _beforeSave () {
    return $this->isValueChanged()
           ? $this->setValue(
               '/' . trim($this->getValue(), "/ \t\n\r\0\x0B") . '/'
             )
           : $this;
  }
}
