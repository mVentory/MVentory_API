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

$tableName = 'mventory/matching_rules';

$idxName = $this->getIdxName($tableName, array('attribute_set_id'));

$fkName = $this->getFkName($tableName,
                           'attribute_set_id',
                           'eav/attribute_set',
                           'attribute_set_id');

$connection = $this->getConnection();

$table = $connection
           ->newTable($this->getTable($tableName))
           ->addColumn('id',
                       Varien_Db_Ddl_Table::TYPE_INTEGER,
                       null,
                       array(
                         'identity' => true,
                         'unsigned' => true,
                         'nullable' => false,
                         'primary' => true,
                       ),
                       'Primary key')
           ->addColumn('attribute_set_id',
                       Varien_Db_Ddl_Table::TYPE_SMALLINT,
                       null,
                       array(
                         'unsigned' => true,
                         'nullable' => false,
                         'default' => '0'
                       ),
                       'Attribute Set ID')
           ->addColumn('rules',
                       Varien_Db_Ddl_Table::TYPE_TEXT,
                       null,
                       array(
                         'nullable' => true,
                       ),
                       'Rule data in JSON')
           ->addIndex($idxName, array('attribute_set_id'))
           ->addForeignKey($fkName,
                           'attribute_set_id',
                           $this->getTable('eav/attribute_set'),
                           'attribute_set_id',
                           Varien_Db_Ddl_Table::ACTION_CASCADE,
                           Varien_Db_Ddl_Table::ACTION_CASCADE)
           ->setComment('Matching rules');

$connection->createTable($table);

$this->endSetup();
