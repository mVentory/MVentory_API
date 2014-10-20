<?php

class MVentory_API_Model_Dataflow_Convert_Adapter_Order extends Mage_Dataflow_Model_Convert_Adapter_Abstract{
  
  const MULTI_DELIMITER = ' , ';
  protected $_attributes = array();
  
  protected $_store;
  protected $_filter = array();

  
  /**
   * @param $attrFilter - $attrArray['attrDB']   = ['like','eq','fromTo','dateFromTo]
   * @param $attrToDb    - attribute name to DB field
   * 
  */
  protected function _parseVars()
  {
      $varFilters = $this->getVars();
      $filters = array();
      foreach ($varFilters as $key => $val) {
          if (substr($key,0,6) === 'filter') {
              $keys = explode('/', $key, 2);
              $filters[$keys[1]] = $val;
          }
      }
      return $filters;
  }
    
    
  public function load(){
    
    $attrFilterArray = array();
    
    $attrFilterArray['created_at'] = 'datetimeFromTo';
    if ($var = $this->getVar('filter/created_at/from')) {
        
        $this->setVar('filter/created_at/from', $var . ' 00:00:00');
    }
    if ($var = $this->getVar('filter/created_at/to')) {
        $this->setVar('filter/created_at/to', $var . ' 23:59:59');
    }
    
    $attrFilterArray['updated_at'] = 'datetimeFromTo';
    if ($var = $this->getVar('filter/updated_at/from')) {
        $this->setVar('filter/updated_at/from', $var . ' 00:00:00');
    }
    if ($var = $this->getVar('filter/updated_at/to')) {
        $this->setVar('filter/updated_at/to', $var . ' 23:59:59');
    }    

    
    if ($storeId = $this->getVar('filter/store_id')) {
            $this->_filter[] = array(
                'attribute' => 'store_id',
                'eq'        => $storeId
            );
    }
    
    if ($state = $this->getVar('filter/state')) {
        $attrFilterArray['state'] = 'eq';
    }
    
    
    if ($period = $this->getVar('filter/period')) {
        $date = "";
        if($period=="today"){
            $this->setVar('filter/created_at/from', date('m/d/Y'). ' 00:00:00');
        }elseif($period=="week"){
            #$current_dayname = date("l");
            $date = date("m/d/Y",strtotime('monday this week'));
            #date("Y-m-d",strtotime("$current_dayname this week"));
            $this->setVar('filter/created_at/from', $date. ' 00:00:00');
        }elseif($period=="month"){
            $date = date('Y-m-01');
            Mage::log($date);  
        }elseif($period=="7"){
            $date=date("m/d/Y", strtotime('-7 day'));
        }elseif($period=="30"){
            $date=date("m/d/Y", strtotime('-30 day'));
        }elseif($period=="year"){
            $date = date('Y-01-01');
        }
        $this->setVar('filter/created_at/from', $date. ' 00:00:00');
    }
    
    
    
    ///populates this->_filter
    $this->setFilter($attrFilterArray);
    
    
    $coll = Mage::getModel('sales/order')->getCollection();
    
    foreach ($this->_filter as $val) {
        $coll->addFieldToFilter( $val['attribute'], $val );
    }
    #Mage::log($coll->getSelect()->__toString());
    
    $entityIds = $coll->getAllIds();
    
    $this->setData($entityIds);
    return $this;
  }//load
    
    
    
    
    
    
  /**
  *
  *
  */
  public function setFilter($attrFilterArray){
      
    
    $filters = $this->_parseVars();
     
    foreach ($attrFilterArray as $key => $type) {
      
        if ($type == 'dateFromTo' || $type == 'datetimeFromTo') {
                foreach ($filters as $k => $v) {
                    if (strpos($k, $key . '/') === 0) {
                        $split = explode('/', $k);
                        $filters[$key][$split[1]] = $v;
                    }
                }
        }
        
        
        $val = isset($filters[$key]) ? $filters[$key] : null;
        if (is_null($val)) {
            continue;
        }
        
        
        ##$keyDB = (isset($this->_attrToDb[$key])) ? $this->_attrToDb[$key] : $key;
        $keyDB =  $key;
        
        
        $attr = array();
        switch ($type){
            case 'eq':
                $attr = array(
                    'attribute' => $keyDB,
                    'eq'        => $val
                );
                break;
            case 'like':
                $attr = array(
                    'attribute' => $keyDB,
                    'like'      => '%'.$val.'%'
                );
                break;
            case 'startsWith':
                 $attr = array(
                     'attribute' => $keyDB,
                     'like'      => $val.'%'
                 );
                 break;
            case 'fromTo':
                $attr = array(
                    'attribute' => $keyDB,
                    'from'      => $val['from'],
                    'to'        => $val['to']
                );
                break;
            case 'dateFromTo':
                $attr = array(
                    'attribute' => $keyDB,
                    'from'      => $val['from'],
                    'to'        => $val['to'],
                    'date'      => true
                );
                break;
            case 'datetimeFromTo':
                $attr = array(
                    'attribute' => $keyDB,
                    'from'      => isset($val['from']) ? $val['from'] : null,
                    'to'        => isset($val['to']) ? $val['to'] : null,
                    'datetime'  => true
                );
                break;
            default:
            break;
        }
        $this->_filter[] = $attr;
    }
    return $this;
  }
    
  
  
  /**
  * Retrieve store Id
  *
  * @return int
  */
  public function getStoreId()
  {
    if (is_null($this->_store)) {
        try {
            $this->_store = Mage::app()->getStore($this->getVar('store'));
        }
        catch (Exception $e) {
            $message = Mage::helper('eav')->__('Invalid store specified');
            $this->addException($message, Varien_Convert_Exception::FATAL);
            throw $e;
        }
    }
    return $this->_store->getId();
  }  
    
    
  
  
  
  
  public function save(){
  }
  
  
}