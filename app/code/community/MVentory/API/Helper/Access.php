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
 * @copyright Copyright (c) 2016 mVentory Ltd. (http://mventory.com)
 * @license http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

/**
 * API access helper
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Helper_Access extends MVentory_API_Helper_Data
{
  /**
   * Generate access key and store it
   *
   * @param Mage_Api_Model_User $apiUser
   *   API user model
   *
   * @param Mage_Core_Model_Store $store
   *   Store model
   *
   * @return string|null
   *   Generated access key
   */
  public function createKey ($apiUser, $store) {
    $periodEnd = $this->_getPeriodEnd($store);
    if (!$periodEnd)
      return;

    $keys = $this->_loadKeys($store);
    $key = $this->randomString(MVentory_API_Model_Config::ACCESS_KEY_LENGTH);

    $keys[$key] = [
      'valid_until' => $periodEnd,
      'api_user_id' => $apiUser->getId()
    ];

    $this->_saveKeys($keys, $store);

    return $key;
  }

  /**
   * Validate supplied access key and return data assigned to the key
   *
   * @param string $key
   *   Access key
   *
   * @param Mage_Core_Model_Store $store
   *   Store model
   *
   * @return array|null
   *   Data assigned to the key
   */
  public function isKeyValid ($key, $store) {
    //Key string is incorrect
    if (!$key || strlen($key) != MVentory_API_Model_Config::ACCESS_KEY_LENGTH)
      return;

    //Key doesn't exist
    $keys = $this->_loadKeys($store);
    if (!($keys && isset($keys[$key])))
      return;

    //Key doesn't contain required data
    $keyData = $keys[$key];
    if (!isset($keyData['valid_until'], $keyData['api_user_id']))
      return;

    //Key expired
    if (microtime(true) > (float) $keyData['valid_until'])
      return;

    //Remove used key
    unset($keys[$key]);
    $this->_saveKeys($keys, $store);

    return $keyData;
  }

  /**
   * Create URL with supplied access key
   *
   * @param string $key
   *   Access key
   *
   * @param Mage_Core_Model_Store $store
   *   Store model
   *
   * @return string
   *   URL
   */
  public function getLink ($key, $store) {
    return Mage::getModel('core/url')
      ->setStore($store)
      ->getDirectUrl('mventory-key/' . $key);
  }

  /**
   * Wrap URL with access key with link to its QR image
   *
   * @param string $link
   *   URL with access key
   *
   * @return string
   *   URL to QR image for supplied URL
   */
  public function getQrLink ($link) {
    return 'https://chart.googleapis.com/chart?cht=qr&chld=M|1&chs=300x300&chl='
           . urlencode($link);
  }

  /**
   * Get lifetime of access key in minutes
   *
   * @param Mage_Core_Model_Store $store
   *   Store model
   *
   * @return int
   *   Lifetime of access key in minutes
   */
  public function getAccessLinkLifetime ($store) {
    return (int) $store->getConfig(MVentory_API_Model_Config::_LINK_LIFETIME);
  }

  /**
   * Add 'Generate mVentory Access Link' button to specified block
   *
   * @param Mage_Adminhtml_Block_Widget_Container $block
   *   Block to add link to
   *
   * @param Mage_Api_Model_User apiUser
   *   API user model
   *
   * @return MVentory_API_Helper_Access
   *   Instance of this class
   */
  public function addGenerateLinkButton ($block, $apiUser) {
    $block->addButton(
      'generate_access_link',
      [
        'label' => $this->__('Generate mVentory Access Link'),
        'onclick' => sprintf(
          'if (confirm(\'%s\')) setLocation(\'%s\')',
          $this->__('Allow database access via mVentory app?'),
          $this->_getGenerateLinkUrl($apiUser)
        ),
        'class' => 'add'
      ],
      -1
    );

    return $this;
  }

  /**
   * Check if first API user has been created
   *
   * @param Mage_Core_Model_Store $store
   *   Store model
   *
   * @return boolean
   *   Result of the check
   */
  public function isUserCreated ($store) {
    return Mage::getStoreConfigFlag(
      MVentory_API_Model_Config::_ACCESS_USER_CREATED,
      $store
    );
  }

  /**
   * Set that first API user was created
   *
   * @param Mage_Core_Model_Store $store
   *   Store model
   */
  public function setUserCreated ($store) {
    return $this->saveConfig(
      MVentory_API_Model_Config::_ACCESS_USER_CREATED,
      true,
      $store
    );
  }

  /**
   * Load all access keys and data for specified store
   *
   * @todo For multistore support it requires custom loading of data from
   *   the config. When a store is not admin store and there's
   *   no a store-specific value then it should not fallback and should not load
   *   data from admin store. Treat admin store as any other store.
   *   _saveKeys() method doesn't require similar change.
   *
   * @param Mage_Core_Model_Store $store
   *   Store model
   *
   * @return array
   *   Access keys and data
   */
  protected function _loadKeys ($store) {
    try {
      $keys = Mage::helper('core')->jsonDecode(
        $store->getConfig(MVentory_API_Model_Config::_ACCESS_KEYS)
      );
    }
    catch (Exception $e) {
      return [];
    }

    return ($keys && is_array($keys)) ? $keys : [];
  }

  /**
   * Save list of access key and data to the config storage of specified store
   *
   * @param array $keys
   *   List of access keys and data
   *
   * @param Mage_Core_Model_Store $store
   *   Store model
   *
   * @return MVentory_API_Helper_Access
   *   Instance of this class
   */
  protected function _saveKeys ($keys, $store) {
    return $this->saveConfig(
      MVentory_API_Model_Config::_ACCESS_KEYS,
      Mage::helper('core')->jsonEncode($keys),
      $store
    );
  }

  /**
   * Calculate last second of lifetime of access key
   *
   * @param Mage_Core_Model_Store $store
   *   Store model
   *
   * @return float
   *  Last second of lifetime of access key
   */
  protected function _getPeriodEnd ($store) {
    $period = $this->getAccessLinkLifetime($store) * 60;
    if (!$period)
      return;

    return microtime(true) + $period;
  }

  /**
   * Create URL for 'Generate mVentory Access Link' button
   *
   * @param Mage_Api_Model_User $apiUser
   *   API user model
   *
   * @return string
   *   URL for 'Generate mVentory Access Link' button
   */
  protected function _getGenerateLinkUrl ($apiUser) {
    return Mage::getModel('adminhtml/url')->getUrl(
      'adminhtml/mventory_access/generatelink',
      [
        'user_id' => $apiUser->getId(),
      ]
    );
  }
}
