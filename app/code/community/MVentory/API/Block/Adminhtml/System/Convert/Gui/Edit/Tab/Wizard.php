<?php
/**
* NOTICE OF LICENSE
*
* This source file is subject to the Creative Commons License BY-NC-ND.
* NonCommercial — You may not use the material for commercial purposes.
* NoDerivatives — If you remix, transform, or build upon the material,
* you may not distribute the modified material.
* See the full license at http://creativecommons.org/licenses/by-nc-nd/4.0/
*
* See http://mventory.com/legal/licensing/ for other licensing options.
*
* @package MVentory/API
* @copyright Copyright (c) 2014 mVentory Ltd. (http://mventory.com)
* @license http://creativecommons.org/licenses/by-nc-nd/4.0/
*/


class MVentory_API_Block_Adminhtml_System_Convert_Gui_Edit_Tab_Wizard  extends Mage_Adminhtml_Block_System_Convert_Gui_Edit_Tab_Wizard  {
  
  public function __construct(){
      parent::__construct();
      $this->setTemplate('mventory/system/convert/profile/wizard.phtml');
  }
    
  /**
  * Add store_id as a gui dropdown filter
  */
  public function getOrderStoreFilterOptions(){
      
    #$filterStores = array(''=>$this->__('Any Store'));
    $filterStores = array();
    $allStores = Mage::app()->getStores();
    foreach ($allStores as $_eachStoreId => $_store)
    {
          #$_store = Mage::app()->getStore($_eachStoreId);
          $filterStores[$_store->getId()] = $_store->getName();
    }
      
      return $filterStores;
  }
  
  
  public function getOrderStatusFilterOptions(){
    
    $statuses = Mage::getModel('sales/order_status')->getResourceCollection()->getData();
    $pairs = array();
    foreach($statuses as $_pair){
        $pairs[$_pair['status']] = $_pair['label'];
    }
    return $pairs;
  }
  
  
  public function getAttributes($entityType){
    if (!isset($this->_attributes[$entityType])) {
            switch ($entityType) {
                case 'product':
                    $attributes = Mage::getSingleton('catalog/convert_parser_product')
                        ->getExternalAttributes();
                    break;
                case 'customer':
                    $attributes = Mage::getSingleton('customer/convert_parser_customer')
                        ->getExternalAttributes();
                  
                    break;
                
                
                ///for orders
                case 'order':
                  $attributes = Mage::getSingleton('mventory/dataflow_convert_parser_order')
                        ->getExternalAttributes();
                  break;
                  
            }

            array_splice($attributes, 0, 0, array(''=>$this->__('Choose an attribute')));
            $this->_attributes[$entityType] = $attributes;
    }
    return $this->_attributes[$entityType];
  }
  
  
}