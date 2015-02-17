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
 * EAV attribute resource model
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Model_Resource_Entity_Attribute
  extends Mage_Eav_Model_Resource_Entity_Attribute {

  /**
    * Retrieve attribute id by attribute label and store id
    *
    * @param string $label
    * @param int $storeId
    * @return int
    */
  public function getIdByLabel ($label, $storeId) {
    $adapter = $this->_getReadAdapter();

    $bind = array(':attribute_label' => $label);

    $select = $adapter
                ->select()
                ->from(
                    array('a' => $this->getTable('eav/attribute')),
                    array('a.attribute_id')
                  );

    if ($storeId) {
      $select
        ->joinLeft(
            array('l' => $this->getTable('eav/attribute_label')),
            'a.attribute_id = l.attribute_id AND l.store_id = :store_id',
            array('store_label'
                       => $adapter->getIfNullSql('l.value', 'a.frontend_label'))
          )
        ->having('store_label = :attribute_label');

      $bind[':store_id'] = $storeId;
    } else
      $select
        ->where('a.frontend_label = :attribute_label');

    return $adapter->fetchOne($select, $bind);
  }

  /**
    * Retrieve default value by attribute label and store id
    *
    * @param string $label
    * @param int $storeId
    * @return string
    */
  public function getDefaultValueByLabel ($label, $storeId) {
    $adapter = $this->_getReadAdapter();

    $bind = array(':attribute_label' => $label);

    $select = $adapter
                ->select()
                ->from(
                    array('a' => $this->getTable('eav/attribute')),
                    array('a.default_value')
                  );

    if ($storeId) {
      $select
        ->joinLeft(
            array('l' => $this->getTable('eav/attribute_label')),
            'a.attribute_id = l.attribute_id AND l.store_id = :store_id',
            array('store_label'
                       => $adapter->getIfNullSql('l.value', 'a.frontend_label'))
          )
        ->having('store_label = :attribute_label');

      $bind[':store_id'] = $storeId;
    } else
      $select
        ->where('a.frontend_label = :attribute_label');

    return $adapter->fetchOne($select, $bind);
  }
}

?>
