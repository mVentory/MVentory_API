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

/*
* This exports the csv file
*/
class MVentory_API_Model_Dataflow_Convert_Parser_Order extends  Mage_Dataflow_Model_Convert_Parser_Abstract {
  
  /**
  * This supplies the list of column headers that can be renamed/mapped in the csv file.
  *
  */
  public function getExternalAttributes(){
    
    $internal = array(
            #'store_id',
            #'entity_id',
            #'website_id',
            #'group_id',
    );
    
    $attributes = array(
    );
    
    
    $order = Mage::getModel('sales/order')->getCollection()->addAttributeToSelect('*')->getFirstItem();
    foreach ($order->getData() as $colName => $v) {
            
            if (in_array($colName, $internal) ) {
                continue;
            }
            $attributes[$colName] = $colName;
    }
    
    return $attributes;
  }
  
  
  
  
  

  /**
  * This extracts the data for export.
  *
  */
  public function unparse(){
    
    $entityIds = $this->getData();

    $coll = Mage::getModel('sales/order')->getCollection();
    $coll->addFieldToFilter('entity_id', array('in'=> $entityIds));
    $coll->load();
        
    ///data
    foreach ($coll->getData() as $i => $_order) {
        
        $row = array();
        foreach($_order as $k=>$v){
            $row[$k] = $v;
        }
        
        $batchExport = $this->getBatchExportModel()
        ->setId(null)
        ->setBatchId($this->getBatchModel()->getId())
        ->setBatchData($row)
        ->setStatus(1)
        ->save();
    }
    
        
    return $this;
  }
  
  public function parse(){
    return $this;
  }
  
}