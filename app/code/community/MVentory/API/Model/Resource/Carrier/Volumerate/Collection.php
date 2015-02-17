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
 * Collection for the volume based shipping carrier model
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Model_Resource_Carrier_Volumerate_Collection
  extends Mage_Shipping_Model_Resource_Carrier_Tablerate_Collection {

  /**
   * Define resource model and item
   */
  protected function _construct () {
    $this->_init('mventory/carrier_volumerate');

    $this->_shipTable = $this->getMainTable();
    $this->_countryTable = $this->getTable('directory/country');
    $this->_regionTable = $this->getTable('directory/country_region');
  }
}
