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
 * Android application API
 *
 * @package MVentory/API
 */
class MVentory_API_Model_App_Api extends Mage_Api_Model_Resource_Abstract
{
  /**
   * Return various store specific setting used in Android application
   *
   * @return array
   *   Various store specific setting
   */
  public function config () {
    $helper = Mage::helper('mventory/currency');

    return [
      'currency_format' => $helper->getFormat($helper->getCurrentStore())
    ];
  }
}
