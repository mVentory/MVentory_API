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
 * @copyright Copyright (c) 2015 mVentory Ltd. (http://mventory.com)
 * @license http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

/**
 * String utils
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Helper_String extends Mage_Core_Helper_String
{
  /**
   * Search backwards starting from haystack length characters from the end
   *
   * @param string $haystack
   *   Haystack string
   *
   * @param string $needle
   *   Needle string
   *
   * @return bool
   *   Result of the check
   */
  public function startsWith ($haystack, $needle) {
    return $needle === ""
           || strrpos($haystack, $needle, -strlen($haystack)) !== false;
  }

  /**
   * Search forward starting from end minus needle length characters
   *
   * @param string $haystack
   *   Haystack string
   *
   * @param string $needle
   *   Needle string
   *
   * @return bool
   *   Result of the check
   */
  public function endsWith ($haystack, $needle) {
    return $needle === ""
           || strpos($haystack, $needle, strlen($haystack) - strlen($needle))
                !== false;
  }
}