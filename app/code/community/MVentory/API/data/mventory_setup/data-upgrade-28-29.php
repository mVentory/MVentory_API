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

$res = Mage::getModel('core/resource');
$conn = $this->getConnection();

$table = $res->getTableName('mventory/matching_rules');

$select = $conn
  ->select()
  ->from($table, array('id', 'rules'));

$this->startSetup();

foreach ($conn->fetchPairs($select) as $id => $rules) {
  $rules = unserialize($rules);

  foreach ($rules as $ruleId => &$rule) {
    if (!isset($rule['category']))
      continue;

    if (isset($rule['categories'])
        && in_array($rule['category'], $rule['categories'])) {
      unset($rule['category']);
      continue;
    }

    $rule['categories'][] = $rule['category'];
    unset($rule['category']);
  }

  $conn->update(
    $table,
    array('rules' => serialize($rules)),
    array('id = ?' => $id)
  );
}

$this->endSetup();