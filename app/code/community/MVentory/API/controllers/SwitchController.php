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
 * Controller for switching between desktop and mobile themes
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_SwitchController
  extends Mage_Core_Controller_Front_Action {

  public function toMobileAction() {
    $storeId = Mage::app()->getStore()->getId();
    Mage::getModel('core/cookie')->set('site_version_' . $storeId,
                                       'mobile',
                                       3600 * 24 * 7,
                                       '/');
    if($this->_isUrlInternal($this->_getRefererUrl())) {
      return $this->_redirectUrl($this->_getRefererUrl());
    } else {
      return $this->_redirect("/");
    }
  }

  public function toDesktopAction() {
    $storeId = Mage::app()->getStore()->getId();
    Mage::getModel('core/cookie')->set('site_version_' . $storeId,
                                       'desktop',
                                       3600 * 24 * 7,
                                       '/');
    if($this->_isUrlInternal($this->_getRefererUrl())) {
      return $this->_redirectUrl($this->_getRefererUrl());
    } else {
      return $this->_redirect("/");
    }
  }
}
