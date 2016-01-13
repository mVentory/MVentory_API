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
 * @copyright Copyright (c) 2016 mVentory Ltd. (http://mventory.com)
 * @license http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

/**
 * Metadata helper
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Helper_Metadata extends MVentory_API_Helper_Data
{
  /**
   * Get metadata. If a field name is supplied then it returns field's value
   * otherwise whole metadata.
   *
   * @param Mage_Eav_Model_Entity_Attribute $attribute
   *   Attribute model
   *
   * @param string $field
   *   Metadata field
   *
   * @return mixed
   *   Whole metadata or value of supplied metadata field
   *
   * @throws OutOfBoundsException
   *   If supplied metadata field is not presented in a metadata
   */
  public function get ($attribute, $field = null) {
    $metadata = $attribute['mventory_metadata'];

    if (!is_array($metadata)) {
      $metadata = $this->_parse($metadata);
      $attribute['mventory_metadata'] = $metadata;
    }

    if ($field === null)
      return $metadata;

    $field = (string) $field;

    if (!isset($metadata[$field]))
      throw new OutOfBoundsException();

    return $metadata[$field];
  }

  /**
   * Set new metadata in the attribute or value for specified metadata field
   *
   * @param Mage_Eav_Model_Entity_Attribute $attribute
   *   Attribute model
   *
   * @param array|string $field
   *   New metadata array or name of metadata field
   *
   * @param mixed $value
   *   Value for specified metadata field
   *
   * @return MVentory_API_Helper_Metadata
   *   Instance of this class
   */
  public function set ($attribute, $field, $value = null) {
    if (is_array($field)) {
      $attribute['mventory_metadata'] = $field;
      return $this;
    }

    $metadata = $this->get($attribute);
    $metadata[(string) $field] = $value;
    $attribute['mventory_metadata'] = $metadata;

    return $this;
  }

  /**
   * Append metadata to existing one, overwrite values of existing metadata
   * fields
   *
   * @param Mage_Eav_Model_Entity_Attribute $attribute
   *   Attribute model
   *
   * @param array $data
   *   Metadata to append
   *
   * @return MVentory_API_Helper_Metadata
   *   Instance of this class
   */
  public function append ($attribute, array $data) {
    $this->set($attribute, array_merge($this->get($attribute), $data));

    return $this;
  }

  /**
   * Parse (unserialize) raw metadata string into array
   *
   * @param string $metadata
   *   Raw metadata string
   *
   * @return array
   *   Parsed metadata
   */
  protected function _parse ($metadata) {
    if (!$metadata)
      return [];

    $metadata = unserialize($metadata);

    return ($metadata === false || !is_array($metadata)) ? [] : $metadata;
  }
}
