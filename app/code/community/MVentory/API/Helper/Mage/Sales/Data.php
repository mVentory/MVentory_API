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
 * Sales module base helper
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Helper_Mage_Sales_Data extends Mage_Sales_Helper_Data {

  /**
   * Check allow to send new order confirmation email
   *
   * @param mixed $store
   * @return bool
   */
  public function canSendNewOrderConfirmationEmail ($store = null) {
    return $this->_notApiCall()
           && parent::canSendNewOrderConfirmationEmail($store);
  }

  /**
   * Check allow to send new order email
   *
   * @param mixed $store
   * @return bool
   */
  public function canSendNewOrderEmail ($store = null) {
    return $this->_notApiCall() && parent::canSendNewOrderEmail($store);
  }

  /**
   * Check allow to send order comment email
   *
   * @param mixed $store
   * @return bool
   */
  public function canSendOrderCommentEmail ($store = null) {
    return $this->_notApiCall() && parent::canSendOrderCommentEmail($store);
  }

  /**
   * Check allow to send new shipment email
   *
   * @param mixed $store
   * @return bool
   */
  public function canSendNewShipmentEmail ($store = null) {
    return $this->_notApiCall() && parent::canSendNewShipmentEmail($store);
  }

  /**
   * Check allow to send shipment comment email
   *
   * @param mixed $store
   * @return bool
   */
  public function canSendShipmentCommentEmail ($store = null) {
    return $this->_notApiCall() && parent::canSendShipmentCommentEmail($store);
  }

  /**
   * Check allow to send new invoice email
   *
   * @param mixed $store
   * @return bool
   */
  public function canSendNewInvoiceEmail ($store = null) {
    return $this->_notApiCall() && parent::canSendNewInvoiceEmail($store);
  }

  /**
   * Check allow to send invoice comment email
   *
   * @param mixed $store
   * @return bool
   */
  public function canSendInvoiceCommentEmail ($store = null) {
    return $this->_notApiCall() && parent::canSendInvoiceCommentEmail($store);
  }

  /**
   * Check allow to send new creditmemo email
   *
   * @param mixed $store
   * @return bool
   */
  public function canSendNewCreditmemoEmail ($store = null) {
    return $this->_notApiCall() && parent::canSendNewCreditmemoEmail($store);
  }

  /**
   * Check allow to send creditmemo comment email
   *
   * @param mixed $store
   * @return bool
   */
  public function canSendCreditmemoCommentEmail ($store = null) {
    return $this->_notApiCall()
           && parent::canSendCreditmemoCommentEmail($store);
  }

  /**
   * Check if Magento was requested throw API
   *
   * @return bool
   */
  protected function _notApiCall () {
    return Mage::getSingleton('api/server')->getAdapter() == null;
  }
}
