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
 * Product massactions
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Model_Product_Action extends Mage_Core_Model_Abstract {

  /**
   * Regex to replace tag with atttribute code by its product's value
   *
   * Groups:
   *
   *   - pre, post: whitespaces around tag
   *   - tag: whole tag
   *   - before, after: any text around code group inside tag group
   *   - code: attribute code which is replaced by its value in a product
   *
   * Example:
   *
   * Beach shorts   { in {color} color}   and t-shirt
   *             \A/\_B_/\__C__/\__D__/\E/
   *                \________F________/
   *
   * - A: pre
   * - B: before
   * - C: code
   * - D: after
   * - E: post
   * - F: tag
   *
   * Notes:
   *
   *   - Whole tag is removed when code is empty
   *     Above example become "Beach shorts and t-shirt"
   *   - Any number of spaces around tag is replaced with single space
   *     if tag is removed.
   *
   * @see MVentory_API_Model_Product_Action::_processName()
   */
  const _RE_TAGS = <<<'EOT'
/(?<pre>\s*)(?<tag>{(?<before>[^{}]*){(?<code>[^{}]*)}(?<after>[^{}]*)})(?<post>\s*)/
EOT;


  /**
   * Ignore 'n/a', 'n-a', 'n\a' and 'na' values
   * Note: case insensitive comparing; delimeter can be surrounded
   * with spaces
   */
  const _RE_NA = <<<'EOT'
#^n(\s*[/-\\\\]\s*)?a$#i
EOT;

  public function rebuildNames ($productIds, $storeId) {
    $numberOfRenamedProducts = 0;

    $templates = array();

    $attributeResource
                   = Mage::getResourceSingleton('mventory/entity_attribute');

    foreach ($productIds as $productId) {
      $product = Mage::getModel('catalog/product')
                   ->setStoreId($storeId)
                   ->load($productId);

      $attributeSetId = $product->getAttributeSetId();

      if (!isset($templates[$attributeSetId])) {
        $templates[$attributeSetId] = null;

        $attribueSet = Mage::getModel('eav/entity_attribute_set')
                        ->load($attributeSetId);

        if (!$attribueSet->getId())
          continue;

        $attributeSetName = $attribueSet->getAttributeSetName();

        $defaultValue = $attributeResource
                          ->getDefaultValueByLabel($attributeSetName, $storeId);

        if ($defaultValue) {
          $templates[$attributeSetId] = $defaultValue;

        }
      }

      if (!$templates[$attributeSetId])
        continue;

      $name = $this->_processName($templates[$attributeSetId], $product);

      if ($name == $templates[$attributeSetId])
        continue;

      $name = preg_replace_callback(
        '/(?<needle>\w+)(\s+\k<needle>)+\b/i',
        function ($match) { return $match['needle']; },
        $name
      );

      //Remove duplicates of spaces and punctuation
      $name = preg_replace(
        '/([,.!?;:\s])\1*(\s?)(\2)*(\s*\1\s*)*/',
        '\\1\\2',
        $name
      );

      if ($name && $name != $product->getName()) {
        $product
          ->setName($name)
          ->save();

        ++$numberOfRenamedProducts;
      }
    }

    return $numberOfRenamedProducts;
  }

  public function matchCategories ($productIds) {
    $n = 0;

    foreach ($productIds as $productId) {
      $product = Mage::getModel('catalog/product')->load($productId);

      if (!$product->getId())
        continue;

      $categoryIds = Mage::getModel('mventory/matching')
        ->matchCategory($product);

      if ($categoryIds) {
        $product
          ->setCategoryIds($categoryIds)
          ->save();

        $n++;
      }
    }

    return $n;
  }

  /**
   * Replace {{attribute_code}} tags in the supplied list of product's
   * alternative names with corresponding value from the specified product
   *
   * @see MVentory_TradeMe_Helper_Product::_RE_TAGS
   *   See description of regex
   *
   * @param array $names
   *   List of product's alternative names
   *
   * @param Mage_Catalog_Model_Product $product
   *   Product model
   *
   * @return String
   *    Rebuilt product name
   */
  protected function _processName ($name, $product) {
    $attrs = $product->getAttributes();

    return preg_replace_callback(
        self::_RE_TAGS,
        function ($matches) use ($product, $attrs) {
          $code = trim($matches['code']);

          //We check raw value in the condition because product can contain
          //null/empty string as value and dropdown attribute's frontend
          //returns "No" in this case
          $cond = $code
              && $product->getData($code)
              && isset($attrs[$code])
              && ($attr = $attrs[$code]);

          if ($cond) {

            $value = trim($attr->getFrontend()->getValue($product));

            $value = preg_match(self::_RE_NA, $value) ? false : $value;
          }

          if (isset($value))
            return $matches['pre']
            . $matches['before']
            . $value
            . $matches['after']
            . $matches['post'];

          return ($matches['pre'] . $matches['post']) ? ' ' : '';
        },
        $name
    );
  }
}

?>
