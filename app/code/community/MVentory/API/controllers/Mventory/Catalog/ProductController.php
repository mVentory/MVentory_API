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
 * @copyright Copyright (c) 2014-2015 mVentory Ltd. (http://mventory.com)
 * @license http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

/**
 * Controller for product massactions
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Mventory_Catalog_ProductController
  extends Mage_Adminhtml_Controller_Action {

  protected function _isAllowed () {
    return true;
  }

  public function massNameRebuildAction () {
    $request = $this->getRequest();

    $productIds = (array) $request->getParam('product');
    $storeId = (int) $request->getParam('store', 0);

    try {
      $numberOfRenamed = Mage::getSingleton('mventory/product_action')
                           ->rebuildNames($productIds, $storeId);

      $m = '%d of %d record(s) have been updated.';

      $this
        ->_getSession()
        ->addSuccess($this->__($m, $numberOfRenamed, count($productIds)));
    }
    catch (Mage_Core_Exception $e) {
      $this
        ->_getSession()
        ->addError($e->getMessage());
    } catch (Exception $e) {
      $m = $this->__('An error occurred while updating the product(s) status.');

      $this
        ->_getSession()
        ->addException($e, $m);
    }

    $this->_redirect('adminhtml/catalog_product', array('store'=> $storeId));
  }

  public function massCategoryMatchAction () {
    $request = $this->getRequest();

    $productIds = (array) $request->getParam('product');
    $storeId = (int) $request->getParam('store', 0);

    try {
      $number = Mage::getSingleton('mventory/product_action')
                  ->matchCategories($productIds);

      $m = '%d of %d record(s) have been updated.';

      $this
        ->_getSession()
        ->addSuccess($this->__($m, $number, count($productIds)));
    }
    catch (Mage_Core_Exception $e) {
      $this
        ->_getSession()
        ->addError($e->getMessage());
    } catch (Exception $e) {
      $m = $this->__('An error occurred while updating the product(s) status.');

      $this
        ->_getSession()
        ->addException($e, $m);
    }

    $this->_redirect('adminhtml/catalog_product/', array('store'=> $storeId));
  }

  public function massImageSyncAction () {
    $request = $this->getRequest();

    $productIds = (array) $request->getParam('product');
    $mode = array_flip(explode(',', $request->getParam('mode')));
    $storeId = (int) $request->getParam('store', 0);

    $params = array();

    foreach (array('empty', 'source') as $param)
      if (isset($mode[$param]))
        $params[$param] = true;

    try {
      $numbers = Mage::getSingleton('mventory/product_action')->syncImages(
        $productIds,
        $params,
        $storeId
      );

      $m = '%d of %d record(s) have been updated.';

      $this
        ->_getSession()
        ->addSuccess($this->__($m, $numbers[0], $numbers[1]));
    }
    catch (Mage_Core_Exception $e) {
      $this
        ->_getSession()
        ->addError($e->getMessage());
    } catch (Exception $e) {
      $m = $this->__('An error occurred while syncing the product(s) images.');

      $this
        ->_getSession()
        ->addException($e, $m);
    }

    $this->_redirect('adminhtml/*', array('store'=> $storeId));
  }
}

?>
