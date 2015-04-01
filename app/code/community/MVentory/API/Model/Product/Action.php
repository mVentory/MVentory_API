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
 * Product massactions
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Model_Product_Action extends Mage_Core_Model_Abstract {

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

      $name = implode(' ', $name);

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

  /**
   * Mass action to sync images between configurable product and its
   * assigned simple products
   *
   * @param array $ids
   *   List of various IDs of products. It can be simple and configurable
   *   products
   *
   * @param array $param
   *   List of optional parameter
   *
   * @param int $storeId
   *   Current store ID
   *
   * @return array
   *   Array of result numbers. First number is a number of processed
   *   configurable products and second one is a total number of products
   */
  public function syncImages ($ids, $params, $storeId) {
    if (!$_confs = $this->getConfsAndChildren($ids))
      return array(0, 0);

    $helper = Mage::helper('mventory/image');

    $confs = Mage::getResourceModel('catalog/product_collection')
      ->addAttributeToSelect(array('image', 'small_image', 'thumbnail'))
      ->addIdFilter(array_keys($_confs))
      ->setStore($storeId);

    $n = 0;

    foreach ($confs as $id => $conf) {
      $_params = $params;

      if (isset($_params['source']))
        $_params['source'] = $id;

      $helper->sync(null, $conf, $_confs[$id], $_params);

      $n++;
    }

    return array($n, count($_confs));
  }

  /**
   * Filter IDs of configurable product from supplied products IDs
   *
   * @param array $ids
   *   List of products IDs
   *
   * @return array
   *   Lsit of configurable products IDs
   */
  protected function getConfigurableIds ($ids) {
    $res = Mage::getResourceModel('catalog/product');
    $adp = $res->getReadConnection();

    return $adp->fetchCol(
      $adp
        ->select()
        ->from($res->getEntityTable(), 'entity_id')
        ->where($adp->prepareSqlCondition(
            'type_id',
            Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE
          ))
        ->where($adp->prepareSqlCondition(
            'entity_id',
            array('in' => (array) $ids)
          ))
    );
  }

  /**
   * Return all possible IDs of configirable products and their children IDs
   * from supplied list of products IDs (can be configurable and/or
   * simple products)
   *
   * @param array $ids
   *   List of products IDs (can be configurable and/or simple products)
   *
   * @return array
   *   List of IDs of configirable products and their children IDs
   */
  protected function getConfsAndChildren ($ids) {
    $helper = Mage::helper('mventory/product_configurable');

    $confs = array();
    $children = array();

    //Get configurable IDs from supplied products IDs and load children for them
    foreach (($_confs = $this->getConfigurableIds($ids)) as $id)
      if ($_children = $helper->getChildrenIds($id))
        $children += $confs[$id] = $_children;

    //Return collected configurable IDs and their children if all supplied
    //IDs are IDs of configurable products
    if (count($ids) == count($confs))
      return $confs;

    //Remove all configurable IDs and their children IDs from supplied
    //products IDs and then search for configurables among remained IDs
    if ($ids = array_diff($ids, $_confs, $children))
      while (list($key, $id) = each($ids)) {
        if (($conf = $helper->getIdByChild($id))
            && $children = $helper->getChildrenIds($conf))
          $confs[$conf] = $children;

        $ids = array_diff(
          $ids,
          array($id),
          isset($children) ? $children : array()
        );
      }

    return $confs;
  }
}

?>
