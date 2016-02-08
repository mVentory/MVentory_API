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
 * @copyright Copyright (c) 2014-2015 mVentory Ltd. (http://mventory.com)
 * @license http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

/**
 * Backend model for Local backup folder setting
 *
 * @package MVentory/API
 * @author Bogdan
 */
class MVentory_API_Model_Setting_Backend_Backupdir
  extends Mage_Core_Model_Config_Data
{
  /**
   * Normalize supplied path for local backup directory and make various
   * checks that the directory is usable before saving setting in DB
   *
   * @return MVentory_API_Model_Setting_Backend_Backupdir
   *   Instance of this class
   */
  public function _beforeSave () {
    if (!$this->isValueChanged())
      return $this;

    /**
     * 1. trim both start and end of the value
     * 2. remove any slash symbols from both side of the value string
     * 3. trim again both sides to remove remained whitespaces
     *    from previous step
     *
     * Example: "/ foo/bar/ " -> "foo/bar"
     */
    $val = trim(trim(trim($this->getValue()), '/'));

    if (!$val)
      return $this;

    $this->setValue($val .= '/');

    /**
     * @todo we should check real path that it's still in media directory.
     *       E.g supplied directory path can contain "../"
     */
    $path = Mage::getBaseDir('media') . DS . $val;

    if (!is_dir($path))
      mkdir($path);

    if (!(file_exists($path) && is_dir($path) && is_writable($path)))
      Mage::throwException(
        Mage::helper('mventory')->__(
          'Path "%s" for local backup folder is invalid!',
          $val
        )
      );

    return $this;
  }
}
