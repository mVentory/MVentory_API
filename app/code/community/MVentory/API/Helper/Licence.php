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
 */

/**
 * Licence helper
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Helper_Licence extends MVentory_API_Helper_Data
{

  /**
   * Check if format of supplied licence key is correct and parse it into
   * array of licence data
   *
   * @param string $key Licence key
   * @return array Parsed licence key data
   */
  public function parseKey ($key) {
    $res = preg_match(
      $this->_getPattern(),
      str_replace(array("\r\n", "\r"), "\n", $key),
      $data
    );

    if ($res != 1)
      return;

    return array(
      'domains' => array_map('trim', explode('|', $data['domains'])),
      'signature' => str_replace(array("\r", "\n"), '', $data['signature']),
      '_params' => $data['params']
    );
  }

  /**
   * Check if shop domain address(es) are in the list of domains
   * from the supplied licence key
   *
   * @param  array $key Parsed license key data
   * @return bool
   */
  public function checkDomains ($key) {
    $paths = array(
      Mage_Core_Model_Url::XML_PATH_UNSECURE_URL,
      Mage_Core_Model_Url::XML_PATH_SECURE_URL
    );

    foreach ($paths as $path)
      if ($host = parse_url(Mage::getStoreConfig($path), PHP_URL_HOST))
        $hosts[$host] = $host;

    if (!isset($hosts))
      return false;

    return (bool) array_intersect($hosts, $key['domains']);
  }

  /**
   * Return prapared data from the store's licence key for API user credentials
   *
   * @param mixed $store Store
   * @return string
   */
  public function prepareKeyForAPI ($store = null) {
    $key = Mage::getStoreConfig(
      MVentory_API_Model_Config::_LICENCE_PARSED_KEY,
      $store
    );

    if (!($key && ($key = unserialize($key))))
      return;

    return $key['_params'] . "\n" . $key['signature'] . "\n";
  }

  /**
   * Prepare regexp pattern for licence key
   *
   * @return string
   */
  protected function _getPattern () {
    $comment = '-{5}.+-{5}';

    $lines = array(
      //Comment at the beginning
      $comment,

      //Parameters (single line)
      '(?<params>\|(?:[^|]+\|){6}\|(?<domains>(?:[^|]+\|)*[^|]+)\|)',

      //Signature (multiple lines)
      '(?<signature>(?:.+\v)*.+==)',

      //Comment at the end
      $comment
    );

    return '/^' . implode('\v^', $lines) . '$\v?/m';
  }
}
