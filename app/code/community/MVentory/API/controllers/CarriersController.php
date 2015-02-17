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
 * Volumerate carriers export controller
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_CarriersController
  extends Mage_Adminhtml_Controller_Action {

  protected function _construct() {
    $this->setUsedModuleName('MVentory_API');
  }

  /**
   * Export shipping rates in csv format
   */
  public function exportAction () {
    $websiteId = Mage::app()
                   ->getWebsite($this->getRequest()->getParam('website'))
                   ->getId();

    $content = $this
                 ->getLayout()
                 ->createBlock('mventory/carrier_volumerate_grid')
                 ->setWebsiteId($websiteId)
                 ->getCsvFile();

    $this->_prepareDownloadResponse('shippingrates.csv', $content);
  }
}
