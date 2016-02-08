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
 * Controller for customer
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Mventory_CustomerController
  extends Mage_Adminhtml_Controller_Action
{

  const _NO_ADDRESS = <<<'EOT'
The user must have billing and shipping address for checkout operations.
EOT;

  const _NO_ROLE = <<<'EOT'
Default API role is not configured. Read more on <a target="_blank" href="http://mventory.com/help/api-roles/">http://mventory.com/help/api-roles/</a>
EOT;

  const _USER_EXISTS = <<<'EOT'
User with ID=%d is already registered for API access. Manage details at <a href="%s">API user details page</a>
EOT;

  const _HELP = <<<'EOT'
Manage API access on <a href="%s">API user details page</a>. Save the user there to generate a new access link or visit <a target="_blank" href="http://mventory.com/doc/configuration-2/"> mventory.com</a> for more info.
EOT;

  protected function _construct() {
    $this->setUsedModuleName('MVentory_API');
  }

  protected function _isAllowed () {
    return true;
  }

  /**
   * Create API user for customer
   */
  public function createapiuserAction () {
    $cid = (int) $this->getRequest()->getParam('id');

    if (!$cid)
      return $this->_redirect('adminhtml/customer');

    $customer = Mage::getModel('customer/customer')->load($cid);

    if (!$cid = $customer->getId())
      return $this->_redirect('adminhtml/customer');

    $address = $customer->getPrimaryBillingAddress();

    if (!($address && $address->getId()))
      return $this->_redirectToCustomer($cid, self::_NO_ADDRESS, 'error');

    $address = $customer->getPrimaryShippingAddress();

    if (!($address && $address->getId()))
      return $this->_redirectToCustomer($cid, self::_NO_ADDRESS, 'error');

    unset($address);

    $user = Mage::getModel('api/user')->loadByUsername($cid);

    if ($uid = $user->getUserId())
      return $this->_redirectToCustomer(
        $cid,
        sprintf(
          self::_USER_EXISTS,
          $cid,
          $this->getUrl('adminhtml/api_user/edit', array('user_id' => $uid))
        ),
        'warning'
      );

    $roleName = Mage::helper('mventory')
      ->getConfig(MVentory_API_Model_Config::_DEFAULT_ROLE);

    $role = Mage::getResourceModel('api/role_collection')
      ->addFieldToFilter('role_name', $roleName);

    if (!$role->count())
      return $this->_redirectToCustomer($cid, self::_NO_ROLE, 'error');

    try {
      $user
        ->setUsername($cid)
        ->setFirstname($customer->getFirstname())
        ->setLastname($customer->getLastname())
        ->setEmail($customer->getEmail())
        ->setIsActive(false)
        ->save()
        ->setRoleIds(array($role->getFirstItem()->getRoleId()))
        ->setRoleUserId($user->getUserId())
        ->saveRelations();
    } catch (Exception $e) {
      return $this->_redirectToCustomer($cid, $e->getMessage(), 'error');
    }

    $this->_redirectToCustomer(
      $cid,
      sprintf(
        self::_HELP,
        $this->getUrl(
          'adminhtml/api_user/edit',
          array('user_id' => $user->getUserId())
        )
      )
    );
  }

  private function _redirectToCustomer ($id, $msg = null, $type = 'notice') {
    if ($msg)
      Mage::getSingleton('adminhtml/session')->addMessage(
        Mage::getSingleton('core/message')->$type($this->__($msg))
      );

    return $this->_redirect(
      'adminhtml/customer/edit',
      array(
        'id' => $id,
        '_current' => true
      )
    );
  }
}
