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