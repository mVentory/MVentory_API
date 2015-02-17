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

$role = Mage::getModel('api/roles');
$resources = array();

foreach ($role->getResourcesList2D() as $resource)
  if (strpos($resource, 'mventory') === 0)
    $resources[] = $resource;

$role
  ->setName('mVentory')
  ->setPid(false)
  ->setRoleType('G')
  ->save();

Mage::getModel('api/rules')
  ->setRoleId($role->getId())
  ->setResources($resources)
  ->saveRel();

$this->endSetup();