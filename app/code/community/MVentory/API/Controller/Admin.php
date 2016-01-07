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
 * Base controller for adminhtml controllers
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Controller_Admin
  extends Mage_Adminhtml_Controller_Action
{
  /**
   * Constructor
   */
  protected function _construct() {
    $this->setUsedModuleName('MVentory_API');
  }

  /**
   * ACL check is not implemeted
   *
   * @return boolean
   *   True if access to the controller is allowed for current admin user
   */
  protected function _isAllowed () {
    return true;
  }

  /**
   * Redirects to specified path. Also allows to output message.
   *
   * @param string $path
   *   Path to controller
   *
   * @param array $arguments
   *   Additional arguments passed to Mage_Adminhtml_Model_Url::getUrl()
   *   method
   *
   * @param string $msg
   *   Additional message to output on redirected page
   *
   * @param string $arguments
   *   Type of the addional message
   *
   * @return MVentory_API_Controller_Admin
   *   Instances of this class
   */
  protected function _redirect ($path, $arguments = []) {
    extract($this->_args(
      [null, null, 'msg' => null, 'type' => 'notice'],
      func_get_args()
    ));

    if ($msg)
      Mage::getSingleton('adminhtml/session')->addMessage(
        Mage::getSingleton('core/message')->$type($this->__($msg))
      );

    return parent::_redirect($path, $arguments);
  }

  /**
   * Apply default values for omitted function arguments which are not in
   * the arguments list of the function
   *
   * @param array $defaults
   *   Default values for arguments
   *
   * @param array $args
   *   List all values for arguments passed to the function
   *
   * @return array
   *   List of arguments with applied default values if they were omitted
   *   from the function call
   */
  protected function _args ($defaults, $args) {
    return array_combine(
      array_keys($defaults),
      $args + array_values($defaults)
    );
  }
}
