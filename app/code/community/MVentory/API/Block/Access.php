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
 * API acccess block
 *
 * @todo add this block to layout only on normal admin pages, such as pages
 *   with header, footer, content, etc.
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_Api_Block_Access extends Mage_Adminhtml_Block_Template
{
  protected $_isOutputAllowed = false;

  /**
   * Check if first API user has been created
   *
   * We use only default store because currently we don't support creating
   * API user per store
   *
   * @return boolean
   *   Result of the check
   */
  public function isUserCreated () {
    return Mage::helper('mventory/access')->isUserCreated(
      Mage::app()->getDefaultStoreView()
    );
  }

  /**
   * Get link to mventory_access/createuser action
   *
   * @see MVentory_API_Mventory_AccessController::createuserAction()
   *
   * @return string
   *   Generated link
   */
  public function getCreateUserUrl () {
    return $this->getUrl('adminhtml/mventory_access/createuser');
  }

  /**
   * Get default values for form fieds
   *
   * @return array
   *   List of default values
   */
  protected function _getDefaultValues () {
    $admin = Mage::getSingleton('admin/session')->getUser();
    if (!$admin)
      $admin = new Varien_Object();

    return [
      'email' => $admin->getEmail(),
      'firstname' => $admin->getFirstname(),
      'lastname' => $admin->getLastname(),
      'username' => 'mventory-'
                    . strtolower(Mage::helper('mventory')->randomString(5))
    ];
  }

  /**
   * Preparing global layout
   *
   * @return MVentory_Api_Block_Access
   *   Instance of this class
   */
  protected function _prepareLayout () {
    parent::_prepareLayout();

    if ($this->isUserCreated())
      return $this;

    $headBlock = $this
      ->getLayout()
      ->getBlock('head');

    if (!$headBlock)
      return $this;

    $this->_isOutputAllowed = true;
    $headBlock->addItem('skin_css', 'mventory/css/styles.css');

    return $this;
  }

  /**
   * Generate HTML for the block
   *
   * @todo implement ACL validation before html generation
   *   Mage::getSingleton('admin/session')->isAllowed('...')
   *
   * @return string
   *   Generated HTML
   */
  protected function _toHtml () {
    return $this->_isOutputAllowed ? parent::_toHtml() : '';
  }
}
