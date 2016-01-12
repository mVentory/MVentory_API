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
 * Notifications block
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_Api_Block_Notifications extends Mage_Adminhtml_Block_Template
{
  /**
   * Check if first API user has been created
   *
   * We use only default store because currently we don't support creating
   * API user per store
   *
   * @return boolean
   *   Result of the check
   */
  public function isApiUserCreated () {
    return Mage::helper('mventory/access')->isUserCreated(
      Mage::app()->getDefaultStoreView()
    );
  }

  /**
   * Get link to api_user/new action
   *
   * @see Mage_Adminhtml_Api_UserController::newAction()
   *
   * @return string
   *   Generated link
   */
  public function getNewApiUserUrl () {
    return $this->getUrl('adminhtml/api_user/new');
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
    return $this->isApiUserCreated() ? '' : parent::_toHtml();
  }
}
