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
 * Data helper
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Helper_Data extends Mage_Core_Helper_Abstract {

  protected $_baseMediaUrl = null;

  public function getCurrentWebsite () {
    //Use website stored in the registry. It's used for calls ood API function
    //from non-API context
    if ($website = Mage::registry('mventory_website'))
      return $website;

    $params =  Mage::registry('application_params');

    $hasScopeCode = isset($params)
                      && is_array($params)
                      && isset($params['scope_code'])
                      && $params['scope_code'];

    return $hasScopeCode
             ? Mage::app()->getWebsite($params['scope_code'])
               : Mage::app()->getDefaultStoreView()->getWebsite();
  }

  public function getCurrentStoreId ($storeId = null) {
    if ($storeId)
      return $storeId;

    $website = $this->getCurrentWebsite();

    return $website && $website->getId()
             ? $website->getDefaultStore()->getId() : true;
  }

  /**
   * Get a current API user
   *
   * @return null|Mage_Api_Model_User
   */
  public function getApiUser () {
    $session = Mage::getSingleton('api/session');

    if (!$session->isLoggedIn())
      return;

    return $session->getUser();
  }

  /**
   * Prepare API response:
   *   - Add API version to the beginning of the response data
   *
   * @param array $data API response data
   * @return array Prepared response data
   */
  public function prepareApiResponse ($data) {
    $data = array_reverse($data, true);

    $data['_apiversion_'] = MVentory_API_Model_Config::API_VERSION;

    return array_reverse($data, true);
  }

  /**
   * !!!TODO: remove method; fix dependent code
   */
  public function getWebsitesForProduct () {
    return array($this->getCurrentWebsite()->getId());
  }

  /**
   * Retrieve attribute's raw value from DB for specified product's ID.
   *
   * @deprecated The method is not used in the code. Should be removed
   *
   * @param int $productId
   * @param int|string|array $attribute atrribute's IDs or codes
   * @param int|string|Mage_Core_Model_Website $website Website, its ID or code
   *
   * @return bool|string|array
   */
  public function getAttributesValue ($productId, $attribute, $website = 0) {
    $store = Mage::app()
               ->getWebsite($website)
               ->getDefaultStore();

    return Mage::getResourceModel('mventory/product')
             ->getAttributeRawValue($productId, $attribute, $store);
  }

  /**
   * Update attribute values for product per website
   *
   * @deprecated The method is not used in the code. Should be removed
   *
   * @param int $productId
   * @param array $attrData
   * @param int|string|Mage_Core_Model_Website $website Website, its ID or code
   */
  public function setAttributesValue ($productId, $attrData, $website = 0) {
    $storeId = Mage::app()
                 ->getWebsite($website)
                 ->getDefaultStore()
                 ->getId();

    Mage::getResourceSingleton('catalog/product_action')
      ->updateAttributes(array($productId), $attrData, $storeId);
  }

  /**
   * Return website's base media URL
   *
   * @param Mage_Core_Model_Website $wesite
   *
   * @return string Base media URL
   */
  public function getBaseMediaUrl ($website) {
    if ($this->_baseMediaUrl)
      return $this->_baseMediaUrl;

    $type = Mage_Core_Model_Store::URL_TYPE_MEDIA;

    $secureFlag = Mage::app()->getStore()->isCurrentlySecure()
                    ? 'secure'
                      : 'unsecure';

    $path = 'web/' . $secureFlag . '/base_' . $type . '_url';

    $this->_baseMediaUrl = $this->getConfig($path, $website);

    return $this->_baseMediaUrl;
  }

  public function getConfig ($path, $website = null) {
    $website = Mage::app()->getWebsite($website);

    $config = $website->getConfig($path);

    if ($config === false)
      $config = (string) Mage::getConfig()->getNode('default/' . $path);

    return $config;
  }

  public function isAdminLogged () {
    return Mage::registry('is_admin_logged') === true;
  }

  public function isMobile () {
    $storeId = $this->getCurrentStoreId();
    $code = 'site_version_' . $storeId;
    if(Mage::getModel('core/cookie')->get($code) == 'mobile' ||
       (Mage::getModel('core/cookie')->get($code) === false &&
        Mage::getSingleton('core/session')->getData($code) == 'mobile'))
      return true;

    return false;
  }

  /**
   * Sends email to website's general contant address
   *
   * @param string $subject
   * @param string $message
   * @param int|string|Mage_Core_Model_Website $website Website, its ID or code
   */
  public function sendEmail ($subject,
                             $message,
                             $website = Mage_Core_Model_App::ADMIN_STORE_ID) {

    $email = $this->getConfig('trans_email/ident_general/email', $website);
    $name = $this->getConfig('trans_email/ident_general/name', $website);

    Mage::getModel('core/email')
      ->setFromEmail($email)
      ->setFromName($name)
      ->setToName($name)
      ->setToEmail($email)
      ->setBody($message)
      ->setSubject($subject)
      ->send();
  }

  /**
   * Sends email to website's general contant address using e-mail template
   *
   * @param string $template E-mail template ID
   * @param string $variables Template variables
   * @param int|string|Mage_Core_Model_Website $website Website, its ID or code
   */
  public function sendEmailTmpl ($template, $variables = array(), $website) {
    $path = 'trans_email/ident_general/email';

    $toEmail = $this->getConfig($path, $website);
    $toName = $this->getConfig('trans_email/ident_general/name', $website);

    $fromEmail = $this->getConfig('trans_email/ident_sales/email', $website);
    $fromName = $this->getConfig('trans_email/ident_sales/name', $website);

    Mage::getModel('core/email_template')
      ->loadDefault($template)
      ->setSenderName($fromName)
      ->setSenderEmail($fromEmail)
      ->addBcc((string) Mage::getConfig()->getNode('default/' . $path))
      ->send($toEmail, $toName, $variables);
  }

  public function isObserverDisabled ($observer) {
    $isApiRequest = Mage::getSingleton('api/server')->getAdapter() != null;
    $applyRules = (bool) $this->getConfig(
      MVentory_API_Model_Config::_APPLY_RULES,
      Mage::helper('mventory/product')->getWebsite($observer->getProduct())
    );

    return !($isApiRequest || $applyRules);
  }

  /**
   * Generated random string of requested length
   *
   * @param int $length
   *   Lenght of a generated string
   *
   * @return string
   *   Generated random string
   */
  public function randomString ($length) {
    return substr(
      str_replace(
        ['+', '/', '='],
        '',
        base64_encode(mcrypt_create_iv($length * 2))
      ),
      0,
      $length
    );
  }

  /**
   * Save value on specified path in the Magento config
   *
   * @param string $path
   *   Path for the value in config
   *
   * @param string $value
   *   Value to store
   *
   * @param Mage_Core_Model_Store $store
   *   Store model to specify scope of the config
   *
   * @return MVentory_API_Helper_Data
   *   Instances of this class
   */
  public function saveConfig ($path, $value, $store) {
    if ($store->getId() == Mage_Core_Model_App::ADMIN_STORE_ID) {
      $scope = 'default';
      $scopeId = 0;
    }
    else {
      $scope = 'stores';
      $scopeId = $store->getId();
    }

    Mage::getResourceModel('core/config')->saveConfig(
      $path,
      $value,
      $scope,
      $scopeId
    );

    Mage::app()
      ->getConfig()
      ->reinit();

    return $this;
  }
}
