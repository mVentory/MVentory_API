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
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */

$this->startSetup();

$table = $this
           ->getConnection()
           ->newTable($this->getTable('mventory/cart_item'))
           ->addColumn('transaction_id',
                       Varien_Db_Ddl_Table::TYPE_BIGINT,
                       null,
                       array(
                         'unsigned' => true,
                         'nullable' => false,
                         'primary' => true,
                       ),
                       'Transaction ID')
           ->addColumn('product_id',
                       Varien_Db_Ddl_Table::TYPE_INTEGER,
                       null,
                       array(
                         'unsigned' => true,
                         'nullable' => false
                    ),
                       'Product ID')
           ->addColumn('sku',
                       Varien_Db_Ddl_Table::TYPE_TEXT,
                       64,
                       array(
                         'nullable'  => false
                    ),
                       'SKU')
           ->addColumn('customer_id',
                       Varien_Db_Ddl_Table::TYPE_INTEGER,
                       null,
                       array(
                         'unsigned'  => true,
                         'nullable'  => false
                    ),
                       'Customer ID')
           ->addColumn('qty',
                       Varien_Db_Ddl_Table::TYPE_DECIMAL,
                       '12,4',
                       array(
                         'nullable'  => false
                    ),
                       'QTY')
           ->addColumn('price',
                       Varien_Db_Ddl_Table::TYPE_DECIMAL,
                       '12,4',
                       array(
                         'nullable'  => false
                    ),
                       'Price')
           ->addColumn('total',
                       Varien_Db_Ddl_Table::TYPE_DECIMAL,
                       '12,4',
                       array(
                         'nullable'  => false
                    ),
                       'Total')
           ->addColumn('date_time',
                       Varien_Db_Ddl_Table::TYPE_DATETIME,
                       null,
                       array(
                         'nullable'  => false
                    ),
                       'Date Time')
           ->addColumn('store_id',
                       Varien_Db_Ddl_Table::TYPE_SMALLINT,
                       null,
                       array(
                         'unsigned'  => true,
                         'nullable'  => false
                    ),
                       'Store ID')
           ->addColumn('product_name',
                       Varien_Db_Ddl_Table::TYPE_TEXT,
                       255,
                       array(
                         'nullable'  => false
                    ),
                       'Product name')
           ->addColumn('user_name',
                       Varien_Db_Ddl_Table::TYPE_TEXT,
                       255,
                       array(
                         'nullable'  => false
                    ),
                       'User Name')
           ->addIndex($this->getIdxName('mventory/cart_item',
                                        array('transaction_id')),
                      array('store_id', 'date_time'))
           ->setComment('Custom cart table');

$this->getConnection()->createTable($table);

$this->endSetup();


$sql = <<<____SQL
CREATE PROCEDURE GetCart(IN deleteBefore datetime, IN storeId smallint)
BEGIN
  DELETE FROM ##table_name## WHERE date_time < deleteBefore;
  SELECT * FROM ##table_name## WHERE store_id = storeId ORDER BY date_time DESC;
END;
____SQL;

$sql = str_replace('##table_name##', $this->getTable('mventory/cart_item'), $sql);

$write = Mage::getSingleton('core/resource')->getConnection('core_write');
$write->exec($sql);
