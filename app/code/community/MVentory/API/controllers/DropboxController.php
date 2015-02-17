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
 * Controller for Dropbox web hook
 *
 * @package MVentory/API
 * @author Bogdan
 */
class MVentory_API_DropboxController extends Mage_Core_Controller_Front_Action
{
  public function indexAction () {
    if (!Mage::getStoreConfig('mventory/image_clips/enable'))
      return;

    //Respond to dropbox
    if ($this->getRequest()->getParam('challenge')) {
      echo $this->getRequest()->getParam('challenge');
      return;
    }

    $cache = Mage::app()->getCacheInstance();
    $cursor = $cache->load('deltacursor');

    if (empty($cursor))
      $cursor = null;

    $helper = Mage::helper('mventory/imageclipper');
    $dbxClient = $helper->getDbxClient();

    try {
      $delta = $dbxClient->getDelta($cursor);
      $cache->save($delta['cursor'], 'deltacursor', array('MVENTORY'));
    } catch (Exception $e) {
      return;
    }

    if (!$delta['entries'])
      return;

    $isAutoReplace = Mage::getStoreConfig('mventory/image_clips/auto_replace');

    foreach ($delta['entries'] as $_item) {

      //Add/Edit
      if (!empty($_item['1']) && !empty($_item['0'])) {
        if (!$isAutoReplace)
          continue;

        if (!$helper->isFileInWebFolder($_item['0']))
          continue;

        $hasCorrectType = in_array(
          $_item['1']['mime_type'],
          array('image/jpeg','image/png')
        );

        if (!$hasCorrectType)
          continue;

        //Write new version
        $isUpdated = $helper->copyToMedia($_item['0'], $_item[1]['bytes']);

        if (!$isUpdated)
          continue;

        $helper->removeExcludeFlag(ltrim(basename($_item['0']), '/'));
      }

      //File deleted
      if (empty($_item['1']) && !empty($_item['0']))
        //When an image is deleted from a holding folder (dropbox)
        //the ext should remove EXCLUDE flag from it.
        $helper->removeExcludeFlag(basename($_item['0']));
    }
  }
}
