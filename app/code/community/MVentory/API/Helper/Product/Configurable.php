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
 * Configurable product helper
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Helper_Product_Configurable
  extends MVentory_API_Helper_Product {

  /**
   * Attributes which are ignored for config products on update
   *
   * @see MVentory_API_Helper_Product_Configurable::updateProds()
   */
  protected $_ignUpdInConfig = array(
    'weight' => true,
    'visibility' => true
  );

  public function getIdByChild ($child) {
    $id = $child instanceof Mage_Catalog_Model_Product
            ? $child->getId()
              : $child;

    if (!$id)
      return $id;

    $configurableType
      = Mage::getResourceSingleton('catalog/product_type_configurable');

    $parentIds = $configurableType->getParentIdsByChild($id);

    //Get first ID because we use only one configurable product
    //per simple product
    return $parentIds ? $parentIds[0] : null;
  }

  public function getChildrenIds ($configurable) {
    $id = $configurable instanceof Mage_Catalog_Model_Product
            ? $configurable->getId()
              : $configurable;

    $ids = Mage::getResourceSingleton('catalog/product_type_configurable')
             ->getChildrenIds($id);

    return $ids[0] ? $ids[0] : array();
  }

  public function getSiblingsIds ($product) {
    $id = $product instanceof Mage_Catalog_Model_Product
            ? $product->getId()
              : $product;

    if (!$configurableId = $this->getIdByChild($id))
      return array();

    if (!$ids = $this->getChildrenIds($configurableId))
      return array();

    //Unset product'd ID
    unset($ids[$id]);

    return $ids;
  }

  public function create ($product, $data = array()) {
    $sku = microtime();

    $data['sku'] = 'C' . substr($sku, 11) . substr($sku, 2, 6);

    $data += array(
      'stock_data' => array(
        'is_in_stock' => true
      )
    );

    $data['type_id'] = Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE;
    $data['status'] = Mage_Catalog_Model_Product_Status::STATUS_ENABLED;
    $data['visibility'] = 4;
    $data['name'] = $product->getName(); //???Do we need it?

    //???Set store ID to admin?

    //Reset value of attributes
    $data['product_barcode_'] = null;

    //Load media gallery if it's not loaded automatically (e.g. the product
    //is loaded in collection) to duplicate images
    if (!$product->getData('media_gallery'))
      Mage::getModel('catalog/product_attribute_backend_media')
        ->setAttribute(new Varien_Object(array(
            'id' => Mage::getResourceModel('eav/entity_attribute')
                      ->getIdByCode(
                          Mage_Catalog_Model_Product::ENTITY,
                          'media_gallery'
                        ),
            'attribute_code' => 'media_gallery'
          )))
        ->afterLoad($product);

    $configurable = $product
                      ->setData('mventory_update_duplicate', $data)
                      ->duplicate()
                      //Unset 'is duplicate' flag to prevent duplicating
                      //of images on subsequent saves
                      ->setIsDuplicate(false);

    if ($configurable->getId())
      return $configurable;
  }

  public function getConfigurableAttributes ($configurable) {
    return (($attrs = $configurable->getConfigurableAttributesData()) !== null)
             ? $attrs
               : $configurable
                   ->getTypeInstance()
                   ->getConfigurableAttributesAsArray();
  }

  public function setConfigurableAttributes ($configurable, $attributes) {
    $configurable
      ->setConfigurableAttributesData($attributes)
      ->setCanSaveConfigurableAttributes(true);

    return $this;
  }

  public function addOptions ($configurable, $attribute, $products) {
    $_options = $attribute
                  ->getSource()
                  ->getAllOptions(false, true);

    if (!$_options)
      return $this;

    foreach ($_options as $option)
      $options[(int) $option['value']] = $option['label'];

    unset($_options);

    $id = $attribute->getAttributeId();
    $code = $attribute->getAttributeCode();

    $attributes = $this->getConfigurableAttributes($configurable);

    foreach ($attributes as &$data) {
      if ($data['attribute_id'] != $id)
        continue;

      $usedValues = $this->hasOptions($configurable, $attribute);

      foreach ($products as $product) {
        $value = $product->getData($code);

        if (isset($usedValues[$value]) || !isset($options[$value]))
          continue;

        $label = $options[$value];

        $data['values'][] = array(
          'value_index' => $value,
          'label' => $label,
          'default_label' => $label,
          'store_label' => $label,
          'is_percent' => 0,
          'pricing_value' => ''
        );
      }

      return $this->setConfigurableAttributes($configurable, $attributes);
    }

    return $this;
  }

  public function hasOptions ($configurable, $attribute) {
    $id = $attribute->getAttributeId();

    foreach ($this->getConfigurableAttributes($configurable) as $_attribute) {
      if ($_attribute['attribute_id'] != $id)
        continue;

      if (isset($_attribute['values']) && $_attribute['values']) {
        foreach ($_attribute['values'] as $value)
          $usedValues[(int) $value['value_index']] = true;

        return $usedValues;
      }
    }

    return false;
  }

  public function removeOption ($configurable, $attribute, $product) {
    $id = $attribute->getAttributeId();
    $value = $product->getData($attribute->getAttributeCode());
    $attributes = $this->getConfigurableAttributes($configurable);

    foreach ($attributes as &$_attribute) {
      if (!($_attribute['attribute_id'] == $id
            && isset($_attribute['values'])
            && $_attribute['values']))
        continue;

      foreach ($_attribute['values'] as $valueId => $_value)
        if ($_value['value_index'] == $value)
          unset($_attribute['values'][$valueId]);
    }

    $this->setConfigurableAttributes($configurable, $attributes);

    return $this;
  }

  public function addAttribute ($configurable, $attribute, $products) {
    if ($this->hasAttribute($configurable, $attribute))
      return $this->addOptions($configurable, $attribute, $products);

    $code = $attribute->getAttributeCode();

    //Reset value of configurable attribute in configurable product
    $configurable[$code] = null;

    $attributes = $this->getConfigurableAttributes($configurable);

    $attributes[] = array(
      'label' => $attribute->getStoreLabel(),
      'use_default' => true,
      'attribute_id' => $attribute->getAttributeId(),
      'attribute_code' => $code
    );

    $this->setConfigurableAttributes($configurable, $attributes);

    return $this->addOptions($configurable, $attribute, $products);
  }

  public function hasAttribute ($configurable, $attribute) {
    $id = $attribute->getId();

    foreach ($this->getConfigurableAttributes($configurable) as $attribute)
      if ($attribute['attribute_id'] == $id)
        return true;

    return false;
  }

  public function recalculatePrices ($configurable, $attribute, $products) {
    $code = $attribute->getAttributeCode();

    $prices = array();
    $min = INF;

    //Find minimal price in products
    foreach ($products as $product) {
      if (($price = $this->_getPrice($product)) < $min)
        $min = $price;

      $prices[(int) $product->getData($code)] = $price;
    }

    $id = $attribute->getAttributeId();

    $attributes = $this->getConfigurableAttributes($configurable);

    //Update prices
    foreach ($attributes as &$_attribute)
      if ($_attribute['attribute_id'] == $id) {
        foreach ($_attribute['values'] as &$values)
          if (isset($prices[$values['value_index']]))
            $values['pricing_value'] = $prices[$values['value_index']] - $min;

        break;
      }

    $this->setConfigurableAttributes($configurable, $attributes);

    $configurable->setPrice($min);

    return $this;
  }

  public function assignProducts ($configurable, $products) {
    foreach ($products as $product)
      $ids[] = $product->getId();

    $configurable->setConfigurableProductsData(array_flip(array_merge(
      $configurable->getId()
        ? $configurable->getTypeInstance()->getUsedProductIds()
          : array(),
      $ids
    )));

    return $this;
  }

  public function unassignProduct ($configurable, $product) {
    $ids = array_flip($configurable->getTypeInstance()->getUsedProductIds());

    unset($ids[$product->getId()]);

    $configurable->setConfigurableProductsData($ids);

    return $this;
  }

  /**
   * Merge descriptions of supplied products. Ignore duplicates
   *
   * @param array|Traversable $prods List of products
   * @return string Merged description without duplicates
   */
  public function mergeDescs ($prods) {
    $search = array(' ', "\r", "\n");

    foreach ($prods as $prod) {
      if ($desc = trim($prod->getDescription())) {
        $_desc = strtolower(str_replace($search, '', $desc));

        if (!isset($_descs[$_desc]))
          $descs[] = $desc;

        $_descs[$_desc] = true;
      }

      if ($short = trim($prod->getShortDescription())) {
        $_short = strtolower(str_replace($search, '', $short));

        if (!isset($_shorts[$_short]))
          $shorts[] = $short;

        $_shorts[$_short] = true;
      }
    }

    $res = array();

    if (isset($descs))
      $res['description'] = trim(implode("\r\n", $descs));

    if (isset($shorts))
      $res['short_description'] = trim(implode("\r\n", $shorts));

    return $res;
  }

  /**
   * Update products with specified data and return list of updated products
   *
   * Some attributes are ignored for config prods.
   *
   * @see MVentory_API_Helper_Product_Configurable::_ignUpdInConfig List of
   *   attrs which are ignored in config prods
   * @param array|Traversable $prods List of product to update
   * @param array $data List of attributes to update in $code => $value format
   * @return array List of updated products
   */
  public function updateProds ($prods, $data) {
    $_prods = array();

    foreach ($prods as $prod) {
      $isConfig = $prod->getTypeId()
                    == Mage_Catalog_Model_Product_Type_Configurable::TYPE_CODE;

      foreach ($data as $code => $val) {
        if ($isConfig && isset($this->_ignUpdInConfig[$code]))
          continue;

        $set = $get = $cmp = NULL;

        if (isset($this->_attrSetGet[$code])) {
          extract($this->_attrSetGet[$code]);

          $set = method_exists($prod, $set) ? $set : NULL;
          $get = method_exists($prod, $get) ? $get : NULL;
          $cmp = is_callable($cmp) ? $cmp : NULL;
        }

        $pval = $get ? $prod->$get() : $prod->getData($code);

        /**
         * Use per-product value for visibility attribute
         *
         * @see MVentory_API_Helper_Product_Configurable::_getVisibility
         *   This function returns per-product value for visibility attribute
         *
         * @todo if there will be more attributes which value depends on
         *   the product state and can't be replicated from original product
         *   (which was linked to configurable or was updated while being linked
         *   to configurable) consider to extend format of $data array to
         *   allow to specify source of data (i.e. some function which accepts
         *   the product as parameter and will be called to get data) instead
         *   data.
         */
        if ($code == 'visibility')
          $val = $this->_getVisibility($prod);

        if ($cmp ? call_user_func($cmp, $pval, $val) : ($pval != $val))
          $set ? $prod->$set($val) : $prod->setData($code, $val);
      }

      if ($prod->hasDataChanges())
        $_prods[$prod->getId()] = $prod;
    }

    return $_prods;
  }

  /**
   * - Link product A to B's configurable product (create new configurable C
   *   if product B doesn't have it).
   * - Update values of replicable attributes in product A (use values from
   *   configurable C if it exists or from product B).
   * - Merge description from all products and then share it to all of them.
   * - Synching images between all products.
   *
   * @param Mage_Catalog_Model_Product $a Product A
   * @param Mage_Catalog_Model_Product|int $b Product B
   * @return bool
   */
  public function link ($a, $b) {
    $attrHelper = Mage::helper('mventory/product_attribute');

    $aID = $a->getId();
    $cID = $this->getIdByChild($b);

    if ($cID) {
      $ids = $this->getChildrenIds($cID);

      //Add ID of configurable product to load it; unset ID of currently
      //creating/updating product (A) because it's been already loaded
      $ids[$cID] = $cID;
      unset($ids[$aID]);

      $prods = Mage::getResourceModel('catalog/product_collection')
        ->addAttributeToSelect('*')
        ->addIdFilter($ids)
        ->addStoreFilter($this->getCurrentWebsite()->getDefaultStore())
        ->getItems();

      $prods[$aID] = $a;

      //Merge description while we have array of all products: configurable
      //product (C) and all assigned products (A, B, ...)
      $descs = $this->mergeDescs($prods);

      $c = $prods[$cID];

      //Set description to configurable product (C) if we have merged
      //description. Configurable product (C) will be used as a source of values
      //for replicable attributes thus merged description will be replicated
      //across all assigned products (A, B, ...)
      if ($descs)
        $c->addData($descs);

      unset($prods[$cID], $descs);
    } else {
      if (!($b instanceof Mage_Catalog_Model_Product))
        $b = Mage::getModel('catalog/product')
          ->setStoreId(Mage_Core_Model_App::ADMIN_STORE_ID)
          ->load($b);

      if (!$b->getId())
        return;

      $prods = array(
        $aID => $a,
        $b->getId() => $b
      );

      $c = new Varien_Object();

      //Set to empty array to prevent loading configurable attributes in
      //MVentory_API_Helper_Product_Configurable::getConfigurableAttributes()
      //method because new configurable product doesn't have them
      $c['configurable_attributes_data'] = array();

      //Merge description from products (A and B) and set it to product B
      //Product B will be used as a source of values for replicable attributes
      //thus merged description will be replicated across all products (A and B)
      //and configurable product (C) (it's created by cloning product B)
      if ($descs = $this->mergeDescs($prods))
        $b->addData($descs);

      unset($descs);
    }

    $setId = $cID ? $c->getAttributeSetId() : $b->getAttributeSetId();
    $attr = $attrHelper->getConfigurable($setId);

    //List of attributes and their values which should be updated
    //in all linked products
    $updAttrs = $this->_getUpdAttrs(
      $cID ? $c : $b,
      $setId,
      $attr,
      array(

        /**
         * This dummy value will be overwritten with per-product value
         * in updateProds() function.
         * We pass it just to add visibility attribute to the list.
         *
         * @see MVentory_API_Helper_Product_Configurable::updateProds()
         *   This function applies per-product value
         *
         * @see MVentory_API_Helper_Product_Configurable::_getVisibility
         *   This function returns per-product value for visibility attribute
         */
        'visibility' => true
      )
    );

    $changedProds = $this
      ->addAttribute($c, $attr, $prods)
      ->recalculatePrices($c, $attr, $prods)
      ->assignProducts($c, $prods)
      ->updateProds($prods, $updAttrs);

    if ($cID) {
      //Add configurable product (C) to the list of changed products to save
      //them all together later
      $changedProds[$cID] = $c;
    } else {
      //Create configurable product (C) from product B
      $c = $this->create($b, $c->getData());

      if (!$c->getId())
        return;
    }

    //Unset currently creating/updating product (A) from the list of changed
    //products because it will be saved by caller later
    unset($changedProds[$aID]);

    foreach ($changedProds as $prod)
      $prod->save();

    //Unset currently creating/updating product (A) from the list of products
    //assinged to configurable product (C) because we are passing it
    //in a separate parameter
    unset($prods[$aID]);
    Mage::helper('mventory/image')->sync($a, $c, $prods, true);

    return true;
  }

  public function update ($a, $cID) {
    $attrHelper = Mage::helper('mventory/product_attribute');

    $aID = $a->getId();
    $ids = $this->getChildrenIds($cID);

    //Add ID of configurable product to load it; unset ID of currently
    //creating/updating product (A) because it's been already loaded
    $ids[$cID] = $cID;
    unset($ids[$aID]);

    $prods = Mage::getResourceModel('catalog/product_collection')
      ->addAttributeToSelect('*')
      ->addIdFilter($ids)
      ->addStoreFilter($this->getCurrentWebsite()->getDefaultStore())
      ->getItems();

    //!!!TODO: not sure which product is better to use to get attr set ID
    //in case attribute set was updated
    $setId = $a->getAttributeSetId();
    $attr = $attrHelper->getConfigurable($setId);

    //List of attributes and their values which should be updated
    //in all linked products
    $updAttrs = $this->_getUpdAttrs(
      $a,
      $setId,
      $attr,
      array(

        /**
         * This dummy value will be overwritten with per-product value
         * in updateProds() function.
         * We pass it just to add visibility attribute to the list.
         *
         * @see MVentory_API_Helper_Product_Configurable::updateProds()
         *   This function applies per-product value
         *
         * @see MVentory_API_Helper_Product_Configurable::_getVisibility
         *   This function returns per-product value for visibility attribute
         */
        'visibility' => true
      )
    );

    $prods[$aID] = $a;
    $c = $prods[$cID];
    unset($prods[$cID]);

    //!!!TODO: no need to do it everytime, only when price was changed or
    //         value of configurable attribute was changed
    $this
      ->addOptions($c, $attr, $prods)
      ->recalculatePrices($c, $attr, $prods);

    $prods[$cID] = $c;
    $this->updateProds($prods, $updAttrs);

    unset($prods[$aID]);

    foreach ($prods as $prod)
      $prod->save();
  }

  public function remove ($a, $cID) {
    $aID = $a->getId();
    $ids = $this->getChildrenIds($cID);

    //Add ID of configurable product to load it; unset ID of currently
    //removing product (A) because it's been already loaded
    $ids[$cID] = $cID;
    unset($ids[$aID]);

    $setId = $a->getAttributeSetId();
    $attr = Mage::helper('mventory/product_attribute')->getConfigurable($setId);

    $prods = Mage::getResourceModel('catalog/product_collection')
      ->addAttributeToSelect(array(
          $attr->getAttributeCode(),
          'price'
        ))
      ->addIdFilter($ids)
      ->addStoreFilter($this->getCurrentWebsite()->getDefaultStore())
      ->getItems();

    $c = $prods[$cID];
    unset($prods[$cID]);

    $this
      ->removeOption($c, $attr, $a)
      ->unassignProduct($c, $a)
      ->recalculatePrices($c, $attr, $prods);

    $c->save();
  }

  protected function _getUpdAttrs ($src, $setId, $attr, $overwrite = array()) {
    $helper = Mage::helper('mventory/product_attribute');

    //Get list of attributes with special setter and getter and cache it to
    //use in updateProds() method
    $this->_attrSetGet  = $helper->getAttrsSetGetInfo();

    $attrs = $helper->getReplicables(
      $setId,
      array($attr->getAttributeCode() => true)
    );

    foreach ($attrs as $code) {
      if ($overwrite && isset($overwrite[$code]))
        continue;

      $hasGetter = isset($this->_attrSetGet[$code])
                   && ($get = $this->_attrSetGet[$code]['get'])
                   && method_exists($src, $get);

      $attrs[$code] = $hasGetter ? $src->$get($code) : $src->getData($code);
    }

    return $overwrite ? array_merge($attrs, $overwrite) : $attrs;
  }

  /**
   * Check if supplied product has special price and has no time period
   * for the special price, because we ignore special price if period is set
   *
   * @param Mage_Catalog_Model_Product $prod
   *   Product model
   *
   * @return boolean
   *   True if the product has special price without time period
   */
  protected function _hasSpecialPrice ($prod) {
    if (!isset($this->__now))
      $this->__now = strtotime(Mage::app()->getLocale()->date());

    return is_numeric($prod->getSpecialPrice())
           && !(strtotime($prod->getSpecialFromDate()) >  $this->__now
                || $prod->getSpecialToDate());
  }

  /**
   * Return special price if product has special price otherwise general price
   *
   * @param Mage_Catalog_Model_Product $prod
   *   Product model
   *
   * @return float|string
   *   Special price or general price of product
   */
  protected function _getPrice ($prod) {
    return $this->_hasSpecialPrice($prod)
             ? $prod->getSpecialPrice()
             : $prod->getPrice();
  }

  /**
   * Return 'Catalog, Search' visibility if product has special price
   * otherwise 'Not Visible Individually' visibility
   *
   * @param Mage_Catalog_Model_Product $prod
   *   Product model
   *
   * @return int
   *   'Catalog, Search' or 'Not Visible Individually' visibility
   */
  protected function _getVisibility ($prod) {
    return $this->_hasSpecialPrice($prod)
             ? Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH
             : Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE;
  }
}
