<?php
/**
* This will set visibility to Not visible individually for products without image.
*/
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
require 'app/Mage.php';
Mage::init("shalari","website");
Mage::setIsDeveloperMode(true);

$prods = Mage::getModel('catalog/product')->getCollection()
->addAttributeToSelect('*')
->addAttributeToFilter(array(
        array (
            'attribute' => 'image',
            'like' => 'no_selection'
        ),
        array (
            'attribute' => 'image', // null fields
            'null' => true
        ),
        array (
            'attribute' => 'image', // empty, but not null
            'eq' => ''
        )
        ));


try {
    $pids = $prods->getAllIds();
    #$pids = array(22548);
    $attributes = array('visibility'=>Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE);
    $storeIds = array(14);
    foreach ($storeIds as $storeId){
        Mage::getSingleton('catalog/product_action')->updateAttributes($pids, $attributes, $storeId);
    }

} catch (Exception $e) {
  Mage::printException($e);
}