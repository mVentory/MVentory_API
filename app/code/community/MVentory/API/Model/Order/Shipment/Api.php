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
 * Sales order shippment API
 *
 * @package MVentory/API
 */
class MVentory_API_Model_Order_Shipment_Api extends Mage_Sales_Model_Order_Shipment_Api {

  public function createShipmentWithTracking($orderIncrementId, $carrier,
  $title, $trackNumber, $params = null) {

    $itemsQty = array();
    $comment = null;
    $email = false;
    $includeComment = false;

    if(is_array($params)) {
      if(array_key_exists('itemsQty', $params))
        $itemsQty = $params['itemsQty'];
      if(array_key_exists('comment', $params))
        $comment = $params['comment'];
      if(array_key_exists('email', $params))
        $email = $params['email'];
      if(array_key_exists('includeComment', $params))
        $includeComment = $params['includeComment'];
    }

    $order = Mage::getModel('sales/order')
               ->loadByIncrementId($orderIncrementId);

    if (!$order->getId())
      $this->_fault('order_not_exists');

    $shipmentId = $this->create($orderIncrementId,$itemsQty,$comment,$email,
      $includeComment);

    $this->addTrack($shipmentId,$carrier,$title,$trackNumber);

    $orderApi = Mage::getModel('mventory/order_api');

    return $orderApi->fullInfo($orderIncrementId);
  }

  /**
   * Add tracking number to order
   *
   * This method is redefined to replace not_exists fault
   * with shipment_not_exists to avoid conflicts with similar faults from other
   * modules
   *
   * @param string $shipmentIncrementId
   * @param string $carrier
   * @param string $title
   * @param string $trackNumber
   * @return int
   */
  public function addTrack ($shipmentIncrementId,
                            $carrier,
                            $title,
                            $trackNumber) {

    $shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId(
      $shipmentIncrementId
    );

    if (!$shipment->getId())
      $this->_fault('shipment_not_exists');

    $carriers = $this->_getCarriers($shipment);

    if (!isset($carriers[$carrier]))
      $this->_fault(
        'data_invalid',
        Mage::helper('sales')->__('Invalid carrier specified.')
      );

    $track = Mage::getModel('sales/order_shipment_track')
      ->setNumber($trackNumber)
      ->setCarrierCode($carrier)
      ->setTitle($title);

    $shipment->addTrack($track);

    try {
      $shipment->save();
      $track->save();
    }
    catch (Mage_Core_Exception $e) {
      $this->_fault('data_invalid', $e->getMessage());
    }

    return $track->getId();
  }

  /**
   * Retrieve shipment information
   *
   * This method is redefined to replace not_exists fault
   * with shipment_not_exists to avoid conflicts with similar faults from other
   * modules
   *
   * @param string $shipmentIncrementId
   *   Increment ID of a shipment
   *
   * @return array
   *   Shipment data
   */
  public function info ($shipmentIncrementId) {
    $shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId(
      $shipmentIncrementId
    );

    if (!$shipment->getId())
      $this->_fault('shipment_not_exists');

    $result = $this->_getAttributes($shipment, 'shipment');

    $result['items'] = array();

    foreach ($shipment->getAllItems() as $item)
      $result['items'][] = $this->_getAttributes($item, 'shipment_item');

    $result['tracks'] = array();

    foreach ($shipment->getAllTracks() as $track)
      $result['tracks'][] = $this->_getAttributes($track, 'shipment_track');

    $result['comments'] = array();

    foreach ($shipment->getCommentsCollection() as $comment)
      $result['comments'][] = $this->_getAttributes(
        $comment,
        'shipment_comment'
      );

    return $result;
  }
}
