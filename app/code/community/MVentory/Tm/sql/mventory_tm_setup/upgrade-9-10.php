<?php

$this->startSetup();

$tableName = 'mventory_tm/carrier_volumerate';

$fields = array(
  'website_id',
  'dest_country_id',
  'dest_region_id',
  'dest_zip',
  'condition_name',
  'condition_value'
);

$idxType = Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE;
$idxName = $this->getIdxName($tableName, $fields, $idxType);

$connection = $this->getConnection();

$table = $connection
           ->newTable($this->getTable($tableName))
           ->addColumn('pk',
                       Varien_Db_Ddl_Table::TYPE_INTEGER,
                       null,
                       array(
                         'identity'  => true,
                         'unsigned'  => true,
                         'nullable'  => false,
                         'primary'   => true,
                       ),
                       'Primary key')
           ->addColumn('website_id',
                       Varien_Db_Ddl_Table::TYPE_INTEGER,
                       null,
                       array(
                         'nullable'  => false,
                         'default'   => '0',
                       ),
                       'Website Id')
           ->addColumn('dest_country_id',
                       Varien_Db_Ddl_Table::TYPE_TEXT,
                       4,
                       array(
                         'nullable'  => false,
                         'default'   => '0',
                       ),
                       'Destination coutry ISO/2 or ISO/3 code')
           ->addColumn('dest_region_id',
                       Varien_Db_Ddl_Table::TYPE_INTEGER,
                       null,
                       array(
                         'nullable'  => false,
                         'default'   => '0',
                       ),
                       'Destination Region Id')
           ->addColumn('dest_zip',
                       Varien_Db_Ddl_Table::TYPE_TEXT,
                       10,
                       array(
                         'nullable'  => false,
                         'default'   => '*',
                       ),
                       'Destination Post Code (Zip)')
           ->addColumn('condition_name',
                       Varien_Db_Ddl_Table::TYPE_TEXT,
                       20,
                       array(
                         'nullable'  => false,
                       ),
                       'Rate Condition name')
           ->addColumn('condition_value',
                       Varien_Db_Ddl_Table::TYPE_DECIMAL,
                       '12,4',
                       array(
                         'nullable'  => false,
                         'default'   => '0.0000',
                       ),
                       'Rate condition value')
           ->addColumn('price',
                       Varien_Db_Ddl_Table::TYPE_DECIMAL,
                       '12,4',
                       array(
                         'nullable'  => false,
                         'default'   => '0.0000',
                       ),
                       'Price')
           ->addColumn('cost',
                       Varien_Db_Ddl_Table::TYPE_DECIMAL,
                       '12,4',
                       array(
                         'nullable'  => false,
                         'default'   => '0.0000',
                       ),
                       'Cost')
           ->addIndex($idxName, $fields, array('type' => $idxType))
           ->setComment('Shipping Volumerate');

$connection->createTable($table);

$this->endSetup();