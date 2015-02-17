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
 * Barcode helper
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Helper_Barcode extends MVentory_API_Helper_Data
{
  const UPCA_LENGTH = 12;
  const EAN13_LENGTH = 13;

  /**
   * Check if passed barcode is in EAN13 or UPC-A format.
   * It checks main barcode by checksum and sumpplemental part by its length
   *
   * @param string $barcode Barcode to check
   * @return bool Result of the check
   */
  public function isEAN13 ($barcode) {
    $pattern = '/^(?<barcode>\d{'
               . self::UPCA_LENGTH
               . ','
               . self::EAN13_LENGTH
               . '})(-(?<supplemental>\d{2}|\d{5}))?$/';

    if (preg_match_all($pattern, $barcode, $matches))
      return $this->checksumEAN13($matches['barcode'][0]);

    return false;
  }

  /**
   * Check EAN13 or UPC-A main barcode by calculating checksum digit
   * and comparing it with value from the barcode
   *
   * @param string $barcode Barcode w/o sumpplemental part
   * @return bool Result of the check
   */
  public function checksumEAN13 ($barcode) {

    //Convert to EAN13 format if barcode is UPC-A
    if (strlen($barcode) == self::UPCA_LENGTH)
      $barcode = '0' . $barcode;

    //Ignore last checksum number
    $len = self::EAN13_LENGTH - 1;
    $sum = 0;

    for ($i = 0; $i < $len; $i++)
      $sum += $barcode[$i] * (($i & 1) ? 3 : 1);

    $sum = 10 - ($sum % 10);

    return ($sum == 10 ? 0 : $sum) == (int) $barcode[$len];
  }
}