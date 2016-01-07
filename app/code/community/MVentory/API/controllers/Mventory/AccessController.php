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
 * Controller for setting up API access for the app
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Mventory_AccessController
  extends MVentory_API_Controller_Admin
{

  const _NO_ROLE = <<<'EOT'
Default API role is not configured. Read more on <a target="_blank" href="http://mventory.com/help/api-roles/">http://mventory.com/help/api-roles/</a>
EOT;

  const _LOAD_USER_ERR = <<<'EOT'
API user (ID=%d) can't be loaded
EOT;

  const _KEY_CREATE_ERR = <<<'EOT'
Access key can't be created
EOT;

  //Double percentage sign to screen line breaks from sprintf() variables.
  const __CONFIG_URL = <<<'EOT'
mVentory configuration URL: <a href="%1$s">%1$s</a>
<br>
Email: <a href="mailto:%4$s?subject=API Key&body=Hi,%%0D%%0A%%0D%%0AYour Android access to %3$s has been configured. You can start loading products now.%%0D%%0A%%0D%%0A
Please, download the app from https://play.google.com/store/apps/details?id=com.mventory first and then click on this link to complete the configuration: %1$s %%0D%%0A%%0D%%0A
This link can only be used once within %1hr period. Ask your website administrator to reissue if the link doesn't work or report the problem to support@mventory.com.%%0D%%0A%%0D%%0A
Thanks mVentory">Send API Key Email</a>
<br>
View: <a href="%5$s" target="_blank">QR CODE</a>
EOT;

  /**
   * Generate access link and show it on API user page
   */
  public function generatelinkAction () {
    $apiUserId = (int) $this->getRequest()->getParam('user_id');
    if (!$apiUserId)
      return $this->_redirectToUserList(
        $this->__(self::_LOAD_USER_ERR, $apiUserId),
        'error'
      );

    $apiUser = Mage::getModel('api/user')->load($apiUserId);
    if (!$apiUser->getId())
      return $this->_redirectToUserList(
        $this->__(self::_LOAD_USER_ERR, $apiUserId),
        'error'
      );

    $helper = Mage::helper('mventory/access');
    $store = Mage::app()->getStore(Mage_Core_Model_App::ADMIN_STORE_ID);

    //Create access key
    $key = $helper->createKey($apiUser, $store);
    if (!$key)
      return $this->_redirectToUser(
        $apiUser,
        $this->__(self::_KEY_CREATE_ERR),
        'error'
      );

    $link = $helper->getLink($key, $store);

    $msg = $this->__(
      self::__CONFIG_URL,
      $link,
      round($helper->getAccessLinkLifetime($store) / 60),
      $store->getConfig('web/unsecure/base_url'),
      $apiUser->getEmail(),
      $helper->getQrLink($link)
    );

    $this->_redirectToUser($apiUser, $msg);
  }

  /**
   * Redirect to the page with API users list
   *
   * @param string $msg
   *   Message to show on the page
   *
   * @param string $type
   *   Type of the message
   *
   * @return MVentory_API_Mventory_AccessController
   *   Instance of this class
   */
  protected function _redirectToUserList ($msg, $type = 'notice') {
    return $this->_redirect(
      'adminhtml/api_user',
      ['_current' => true],
      $msg,
      $type
    );
  }

  /**
   * Redirect to the page for specified API user
   *
   * @param Mage_Api_Model_User $apiUser
   *   API user model
   *
   * @param string $msg
   *   Message to show on the page
   *
   * @param string $type
   *   Type of the message
   *
   * @return MVentory_API_Mventory_AccessController
   *   Instance of this class
   */
  protected function _redirectToUser ($apiUser, $msg, $type = 'notice') {
    return $this->_redirect(
      'adminhtml/api_user/edit',
      [
        'id' => $apiUser->getId(),
        '_current' => true
      ],
      $msg,
      $type
    );
  }
}
