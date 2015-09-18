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

  //Add new line
  const _RE_TAGS = <<<'EOT'
/(?<pre>\s*)(?<tag>{(?<before>[^{}]*){(?<code>[^{}]*)}(?<after>[^{}]*)})(?<post>\s*)/
EOT;

  public function rebuildNames ($productIds, $storeId) {
    $numberOfRenamedProducts = 0;

    $templates = array();
    $frontends = array();

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

      //Add new line
      $names = $this->_processNames($templates[$attributeSetId], $product);

      $name = implode(' ', $names);

      if ($names == $templates[$attributeSetId])
        continue;

      $name = trim($names, ', ');

      $name = preg_replace_callback(
        '/(?<needle>\w+)(\s+\k<needle>)+\b/i',
        function ($match) { return $match['needle']; },
        $names
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

  //Add function
  protected function _processNames ($names, $product) {

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

          $value = $cond
              ? trim($attr->getFrontend()->getValue($product))
              : false;

          if ($value)
            return $matches['pre']
            . $matches['before']
            . $value
            . $matches['after']
            . $matches['post'];

          return ($matches['pre'] . $matches['post']) ? ' ' : '';
        },
        $names
    );
  }
}

?>
