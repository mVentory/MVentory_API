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

          $attrs = Mage::getResourceModel('eav/entity_attribute_collection')
                     ->setAttributeSetFilter($attributeSetId);

          foreach ($attrs as $attr) {
            $code = $attr->getAttributeCode();

            if (isset($frontends[$code]))
              continue;

            $resource = $product->getResource();

            $frontends[$code] = $attr
                                  ->setEntity($resource)
                                  ->getFrontend();

            $sortFrontends = true;
          }

          unset($attrs);

          if (isset($sortFrontends) && $sortFrontends)
            uksort(
              $frontends,
              function ($a, $b) { return strlen($a) < strlen($b); }
            );
        }
      }

      if (!$templates[$attributeSetId])
        continue;

      $mapping = array();

      foreach ($frontends as $code => $frontend) {
        $value = $frontend->getValue($product);

        //Try converting value of the field to a string. Set it to empty if
        //value of the field is array or class which doesn't support convertion
        //to string
        try {
          $value = (string) $value;

          //Ignore 'n/a', 'n-a', 'n\a' and 'na' values
          //Note: case insensitive comparing; delimeter can be surrounded
          //      with spaces
          if (preg_match('#^n(\s*[/-\\\\]\s*)?a$#i', trim($value)))
            $value = '';
        } catch (Exception $e) {
          $value = '';
        }

        $mapping[$code] = $value;
      }

      //Sort array by key length (desc)
      uksort($mapping, function ($a, $b) { return strlen($a) < strlen($b); });

      $name = explode(' ', $templates[$attributeSetId]);

      $replace = function (&$value, $key, $mapping) {
        foreach ($mapping as $search => $replace)
          if (($replaced = str_replace($search, $replace, $value)) !== $value)
            return $value = $replaced;
      };

      if (!array_walk($name, $replace, $mapping))
        continue;

      //Add new line
      $names = $this->_processNames($name, $product);

      $name = implode(' ', $names);

      if ($name == $templates[$attributeSetId])
        continue;

      $name = trim($name, ', ');

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
