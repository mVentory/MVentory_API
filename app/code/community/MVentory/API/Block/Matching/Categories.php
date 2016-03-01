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
 * @copyright Copyright (c) 2014-2016 mVentory Ltd. (http://mventory.com)
 * @license http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

/**
 * Categories block
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Block_Matching_Categories
  extends Mage_Adminhtml_Block_Template
{

  /**
   * Get JSON of a tree node or an associative array
   *
   * @param Varien_Data_Tree_Node|array $node
   * @param int $level
   * @return string
   */
  protected function _getNode ($node) {
    $item['text'] = $this->htmlEscape($node->getName());
    $item['id']  = $node->getId();

    if ($node->hasChildren()) {
      $item['children'] = array();

      foreach ($node->getChildren() as $child)
        $item['children'][] = $this->_getNode($child);
    }

    return $item;
  }

  public function getTreeJson () {
    return Mage::helper('core')->jsonEncode($this->_getNode($this->_getTree()));
  }

  /**
   * Get category tree with added collection data (name attribute only)
   *
   * @return Varien_Data_Tree_Node
   *   Loaded category tree
   */
  protected function _getTree () {
    return Mage::getResourceModel('catalog/category_tree')
      ->load()
      ->addCollectionData(
          Mage::getResourceModel('catalog/category_collection')
            ->addAttributeToSelect('name')
        )
      ->getNodeById(Mage_Catalog_Model_Category::TREE_ROOT_ID);
  }
}
