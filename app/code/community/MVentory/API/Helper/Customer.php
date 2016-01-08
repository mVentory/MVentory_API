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
 * @copyright Copright (c) 2016 mVentory Ltd. (http://mventory.com)
 * @license http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

/**
 * API customer helper
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Helper_Customer extends MVentory_API_Helper_Data
{
  /**
   * Default data for a customer used for orders created via our API
   *
   * @var array
   */
  protected $_defaultCustomerData = [
    'firstname' => 'mVentory',
    'lastname' => 'App orders',
    'email' => 'seller@foo.bar'
  ];

  /**
   * Default address data for a customer used for orders created via our API
   *
   * @var array
   */
  protected $_defaultAddressData = [
    'street' => ['Shipping address not specified', ''],
    'suburb' => '',
    'city' => 'City not specified',
    'postcode' => '0000',
    'country' => 'New Zealand',
    'telephone' => 'Telephone not specified'
  ];

  /**
   * Get customer and its default address
   *
   * @param Mage_Core_Model_Store $store
   *   Store model
   *
   * @return array
   *   Customer model and address model
   */
  public function get ($store) {
    return $this->_load($store) ?: $this->_create($store);
  }

  /**
   * Try to load customer model and its default address
   *
   * @param Mage_Core_Model_Store $store
   *   Store model
   *
   * @return array
   *   Customer model and address model
   */
  protected function _load ($store) {
    $id = $this->_loadId($store);
    if (!$id)
      return;

    $customer = Mage::getModel('customer/customer')
      ->setStore($store)
      ->load($id);

    return $customer->getId()
      ? [$customer, $customer->getDefaultBillingAddress()]
      : null;
  }

  /**
   * Create new customer
   *
   * @param Mage_Core_Model_Store $store
   *   Store model
   *
   * @return array
   *   Customer model and address model
   */
  protected function _create ($store) {
    //Prepare data
    $addressData = $this->_prepareAddressData(
      $this->_defaultAddressData,
      $this->_defaultCustomerData
    );

    //Create customer model

    $customer = Mage::getModel('customer/customer')
      ->setStore($store)
      ->setFirstname($this->_defaultCustomerData['firstname'])
      ->setLastname($this->_defaultCustomerData['lastname'])
      ->setEmail($this->_defaultCustomerData['email']);

    try {
      $customer->save();
    }
    catch (Mage_Core_Exception $e) {
      return Mage::logException($e);
    }

    $id = $customer->getId();
    if (!$id)
      return;

    try {
      $address = $this->_createAddress($addressData)
        ->setCustomer($customer)
        ->setIsDefaultBilling(true)
        ->setIsDefaultShipping(true)
        ->save();
    }
    catch (Mage_Core_Exception $e) {
      Mage::logException($e);

      //Remove newly created customer model because we can't get address for it
      //thus it can't be used to create orders
      $customer->delete();

      return;
    }

    //Remember ID of created customer in the store config
    $this->_saveId($id, $store);

    return [$customer, $address];
  }

  /**
   * Create address model using supplied data
   *
   * @param array $data
   *   Address data
   *
   * @return Mage_Customer_Model_Address
   *   Address model
   *
   * @throws Mage_Core_Exception
   *   If supplied address data is not valid
   */
  protected function _createAddress ($data) {
    //Get country ID by country name
    $countryId = Mage::getModel('directory/country')
      ->loadByCode(
          Zend_Locale_Data_Translation::$regionTranslation[$data['country']]
        )
      ->getId();

    //Create address
    $address = Mage::getModel('customer/address')
      ->setFirstname($data['firstname'])
      ->setLastname($data['lastname'])
      ->setStreet($data['street'])
      ->setCity($data['city'])
      ->setCountryId($countryId)
      ->setPostcode($data['postcode'])
      ->setTelephone($data['telephone']);

    if (is_array($valid = $address->validate()))
      throw new Mage_Core_Exception(implode("\n", $valid));

    return $address;
  }

  /**
   * Load customer's ID used for API orders from store's config
   *
   * @param Mage_Core_Model_Store $store
   *   Store model
   *
   * @return int
   *   Customer ID
   */
  public function _loadId ($store) {
    return (int) $store->getConfig(
      MVentory_API_Model_Config::_ORDER_CUSTOMER_ID
    );
  }

  /**
   * Save customer's ID used for API orders in store's config
   * @param int $id
   *   Customer ID
   *
   * @param Mage_Core_Model_Store $store
   *   Store model
   *
   * @return MVentory_API_Helper_Customer
   *   Instance of this class
   */
  public function _saveId ($id, $store) {
    return $this->saveConfig(
      MVentory_API_Model_Config::_ORDER_CUSTOMER_ID,
      $id,
      $store
    );
  }

  /**
   * Prepare address data to create address model
   *
   * @param array $data
   *   Address data
   *
   * @param array $data
   *   Customer data
   *
   * @return array
   *   Prepared address data
   */
  protected function _prepareAddressData ($addressData, $customerData) {
    $addressData['firstname'] = $customerData['firstname'];
    $addressData['lastname'] = $customerData['lastname'];

    return $addressData;
  }
}
