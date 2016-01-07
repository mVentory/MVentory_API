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
 * @copyright Copyright (c) 2014-2016 mVentory Ltd. (http://mventory.com)
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

  public function profileAction () {
    $helper = Mage::helper('mventory/access');

    /**
     * @todo currently we don't support multistore. It requires store selecting
     *   before generating access links which is not implemented. Also it
     *   requires code modification as described in the comment for
     *   MVentory_API_Helper_Access::_loadKeys() method
     */
    $store = Mage::app()->getStore(Mage_Core_Model_App::ADMIN_STORE_ID);

    //Check if supplied access key is valid, not expired and contains
    //all required sata
    $keyData = $helper->isKeyValid(
      trim($this->getRequest()->getParam('key')),
      $store
    );

    //Key is not valid - return 404
    if (!$keyData)
      return $this->norouteAction();

    //Load API user by ID assigned to the access key
    $user = Mage::getModel('api/user')->load($keyData['api_user_id']);
    if (!$user->getId())
      return $this->norouteAction();

    //Generate random password
    $pwd = $helper->randomString(MVentory_API_Model_Config::ACCESS_PWD_LENGTH);

    //Update password of API user and make it active
    $user
      ->setIsActive(true)
      ->setApiKey($pwd)
      ->save();

    //Prepare profile data for the app
    $output = $user->getUsername() . "\n"
              . $pwd . "\n"
              . $store->getBaseUrl(
                  Mage_Core_Model_Store::URL_TYPE_LINK,
                  $store->isAdminUrlSecure()
                )
              . "\n";

    //Output profile data

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

    if (!($key && strlen($key) == MVentory_API_Model_Config::ACCESS_KEY_LENGTH)) {
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
