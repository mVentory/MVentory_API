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
 * Source model for customers field
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Model_System_Config_Source_Customers {

  /**
   * Options getter
   *
   * @return array
   */
  public function toOptionArray () {
    $customers = Mage::getResourceModel('customer/customer_collection')
                   ->addNameToSelect()
                   ->load();

    $options = array();

    foreach ($customers as $customer)
      $options[] = array(
        'value' => $customer->getId(),
        'label' => $customer->getName() . ' ('. $customer->getEmail() . ')'
      );

    return $options;
  }
}

?>
