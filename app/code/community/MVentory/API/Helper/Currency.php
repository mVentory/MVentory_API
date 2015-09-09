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
 * @copyright Copyright (c) 2015 mVentory Ltd. (http://mventory.com)
 * @license http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

/**
 * Currency utils
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Helper_Currency extends MVentory_API_Helper_Data
{
  /**
   * Return format of store's base currency to use in the app
   *
   * @param Mage_Core_Model_Store $store
   *   Store model
   *
   * @return string
   *   Currency format to use in the app
   */
  public function getFormat ($store) {
    $localeModel = Mage::getModel('core/locale');

    return $this->_prepareFormat(
      Zend_Locale_Data::getContent($localeModel->getLocale(), 'currencynumber'),
      $localeModel
        ->currency($store->getBaseCurrencyCode())
        ->getSymbol()
    );
  }

  /**
   * Convert Zend currency format to the one required by the app
   *
   * ¤ is placeholder for currency symbol in Zend format
   * '#', '0', ',', '.' symbols are used for currency number format
   *
   * Example: ¤#,##0.00
   *
   * The example above will be converted to "${0}" if currency symbol is "$" or
   * to "NZ${0}" if symbol is "NZ$"
   *
   * @param string $format
   *   Zend's currency format
   *
   * @param string $symbol
   *   Currency symbol
   *
   * @return [type]         [description]
   */
  protected function _prepareFormat ($format, $symbol) {
    return preg_replace(
      ['/¤/', '/[#0,.]+/'],
      [$symbol, '{0}'],
      $format
    );
  }
}