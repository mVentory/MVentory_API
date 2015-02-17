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

$tableName = $this->getTable('mventory/additional_skus');

$c = $this->getConnection();

$c->dropIndex(
  $tableName,
  $this->getIdxName(
    $tableName,
    array('sku'),
    Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
  )
);

$c->addColumn(
  $tableName,
  'website_id',
  array(
    'type' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
    'unsigned' => true,
    'nullable' => false,
    'comment' => 'Website ID'
  )
);

$c->addIndex(
  $tableName,
  $this->getIdxName(
    $tableName,
    array('sku', 'website_id')
  ),
  array('sku', 'website_id')
);

$websiteTable = $this->getTable('core/website');

$c->addForeignKey(
  $this->getFkName(
    $tableName,
    'website_id',
    $websiteTable,
    'website_id'
  ),
  $tableName,
  'website_id',
  $websiteTable,
  'website_id'
);

$this->endSetup();