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

  const _MISSING_REQUIRED_PARAMS = <<<'EOT'
Some of required parameters are mising
EOT;

  const _NO_ROLE = <<<'EOT'
Default API role is not configured. Read more on <a target="_blank" href="http://mventory.com/help/api-roles/">http://mventory.com/help/api-roles/</a>
EOT;

  const _USER_FAILED = <<<'EOT'
Creation of new API user failed
EOT;

  const _LOAD_USER_ERR = <<<'EOT'
API user (ID=%d) can't be loaded
EOT;

  const _KEY_CREATE_ERR = <<<'EOT'
Access key can't be created
EOT;

  /**
   * Create API user for customer
   */
  public function createuserAction () {
    $requiredFields = [
      'email' => true,
      'firstname' => true,
      'lastname' => true,
      'username' => true
    ];

    $params = array_intersect_key(
      $this->getRequest()->getParams(),
      $requiredFields
    );

    if (count($params) != count($requiredFields))
      return $this->_jsonError($this->__(self::_MISSING_REQUIRED_PARAMS));

    $roleName = Mage::helper('mventory')
      ->getConfig(MVentory_API_Model_Config::_DEFAULT_ROLE);

    $role = Mage::getResourceModel('api/role_collection')
      ->addFieldToFilter('role_name', $roleName);

    if (!$role->count())
      return $this->_jsonError($this->__(self::_NO_ROLE));

    try {
      $apiUser = Mage::getModel('api/user');

      $apiUser
        ->setData($params)
        ->setIsActive(false)
        ->save()
        ->setRoleIds(array($role->getFirstItem()->getRoleId()))
        ->setRoleUserId($apiUser->getUserId())
        ->saveRelations();
    } catch (Exception $e) {
      Mage::logException($e);

      if ($apiUser->getUserId())
        $apiUser->delete();

      return $this->_jsonError($this->__(self::_USER_FAILED), $e);
    }

    $helper = Mage::helper('mventory/access');
    $store = Mage::app()->getStore(Mage_Core_Model_App::ADMIN_STORE_ID);

    //Create access key
    $key = $helper->createKey($apiUser, $store);
    if (!$key)
      return $this->_jsonError($this->__(self::_KEY_CREATE_ERR));

    $info = $this
      ->getLayout()
      ->createBlock(
          'mventory/access_info',
          '',
          [
            'template' => 'mventory/access/info.phtml',
            'store' => $store,
            'access_key' => $key,
            'api_user' => $apiUser
          ]
        )
      ->toHtml();

    $helper->setUserCreated($store);

    $this->_jsonSuccess(['block_info' => $info]);
  }

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

    $info = $this
      ->getLayout()
      ->createBlock(
          'mventory/access_info',
          '',
          [
            'template' => 'mventory/access/info.phtml',
            'store' => $store,
            'access_key' => $key,
            'api_user' => $apiUser
          ]
        )
      ->toHtml();

    $this->_redirectToUser($apiUser, $info);
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
