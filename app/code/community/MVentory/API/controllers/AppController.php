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
 * App controller
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */

class MVentory_API_AppController
  extends Mage_Core_Controller_Front_Action {

  const KEY_LENGTH = 16;

  public function profileAction () {
    $key = $this->getRequest()->getParam('key');

    if (!($key && strlen($key) == self::KEY_LENGTH)) {
      $this->norouteAction();
      return;
    }

    $customer = Mage::getResourceModel('customer/customer_collection')
      ->addAttributeToFilter(
          'mventory_app_profile_key',
          array('like' => $key . '-%')
        );

    if (!$customer->count()) {
      $this->norouteAction();
      return;
    }

    $customer = $customer->getFirstItem();
    $user = Mage::getModel('api/user')->loadByUsername($customer->getId());

    if (!$user->getId()) {
      $this->norouteAction();
      return;
    }

    if (($websiteId = $customer->getWebsiteId()) === null) {
      $this->norouteAction();
      return;
    }

    $store = Mage::app()
      ->getWebsite($websiteId)
      ->getDefaultStore();

    if ($store->getId() === null) {
      $this->norouteAction();
      return;
    }

    $timestamp = (float) substr(
      $customer->getData('mventory_app_profile_key'),
      self::KEY_LENGTH + 1
    );

    if (!($timestamp && microtime(true) < $timestamp)) {
      $this->norouteAction();
      return;
    }

    $apiKey = base64_encode(mcrypt_create_iv(9));

    $user
      ->setIsActive(true)
      ->setApiKey($apiKey)
      ->save();

    $customer
      ->setData('mventory_app_profile_key', '')
      ->save();

    $output = $user->getUsername() . "\n"
              . $apiKey . "\n"
              . $store->getBaseUrl(
                  Mage_Core_Model_Store::URL_TYPE_LINK,
                  $store->isAdminUrlSecure()
                )
              . "\n";

    $response = $this->getResponse();

    $response
      ->setHttpResponseCode(200)
      ->setHeader('Content-type', 'text/plain', true)
      ->setHeader('Content-Length', strlen($output))
      ->clearBody();

     $response->setBody($output);
  }

  public function redirectAction () {
    $key = $this->getRequest()->getParam('key');

    if (!($key && strlen($key) == self::KEY_LENGTH)) {
      $this->norouteAction();
      return;
    }

    $store = Mage::app()->getStore();
    $secure = $store->isAdminUrlSecure();
    $url = $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, $secure);

    $url = ($secure ? 'mventorys://' : 'mventory://')
           . substr($url, strpos($url, '//') + 2)
           . 'mventory-key/'
           . urlencode($key)
           . '.txt';

    $this->_redirectUrl($url);
  }
}
