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