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
 * @copyright Copyright (c) 2014-2015 mVentory Ltd. (http://mventory.com)
 * @license http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

/**
 * Shipping model
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Model_Shipping
  extends Mage_Shipping_Model_Carrier_Abstract
  implements Mage_Shipping_Model_Carrier_Interface {

  protected $_code = 'mventory';
  protected $_isFixed = true;

  public function collectRates (Mage_Shipping_Model_Rate_Request $request) {
    if (!((Mage::getSingleton('api/server')->getAdapter() != null
           || Mage::registry('mventory_allow_shipping'))
          && $this->getConfigFlag('active')))
      return false;

    $method = Mage::getModel('shipping/rate_result_method');

    $method->setCarrier('mventory');
    $method->setCarrierTitle($this->getConfigData('title'));

    $method->setMethod('mventory');
    $method->setMethodTitle($this->getConfigData('name'));

    $method->setPrice('0.00');
    $method->setCost('0.00');

    $result = Mage::getModel('shipping/rate_result');

    $result->append($method);

    return $result;
  }

  public function getAllowedMethods () {
    return array('mventory' => $this->getConfigData('name'));
  }

}
