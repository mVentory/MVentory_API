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
 * API acccess info block
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_Api_Block_Access_Info extends Mage_Adminhtml_Block_Template
{
  /**
   * Internal constructor, that is called from real constructor
   */
  protected function _construct () {
    parent::_construct();

    $this->_helper = Mage::helper('mventory/access');
  }

  /**
   * Generate access URL
   *
   * @return string
   *   Access URL
   */
  protected function _getAccessUrl () {
    return $this
      ->_helper
      ->getLink($this['access_key'], $this['store']);
  }

  /**
   * Generate mailto: URL with email content
   *
   * @param string $subject
   *   Email subject
   *
   * @param string $body
   *   Email content
   *
   * @return string
   *   Mailto: URL
   */
  protected function _getMailtoUrl ($subject, $body) {
    return sprintf(
      'mailto:%s?subject=%s&body=%s',
      $this['api_user']->getEmail(),
      rawurlencode($subject),
      rawurlencode($body)
    );
  }

  /**
   * Get the base URL of current store
   *
   * @return string
   *   Base URL
   */
  protected function _getStoreUrl () {
    return $this['store']->getBaseUrl();
  }

  /**
   * Get period while generated access link is valid
   *
   * @return int
   *   Period in hours
   */
  protected function _getValidPeriod () {
    return round($this->_helper->getAccessLinkLifetime($this['store']) / 60);
  }

  /**
   * Get URL to a QR image contaning supplied URL
   *
   * @param string $accessUrl
   *   URL to encode in the QR image
   *
   * @return string
   *   URL to the QR code
   */
  protected function _getQrImgUrl ($accessUrl) {
    return $this
      ->_helper
      ->getQrLink($accessUrl);
  }

  /**
   * Generate A tag
   *
   * @param string $href
   *   Content of HREF attribute
   *
   * @param string $text
   *   Text
   *
   * @param boolean $newTab
   *   Open link in new window/tab
   *
   * @return string
   *   Generated A tag
   */
  protected function _a ($href, $text = null, $newTab = false) {
    $target = '';

    if ($newTab)
      $target = ' target="_blank"';

    return sprintf('<a href="%s"%s>%s</a>', $href, $target, $text ?: $href);
  }
}
