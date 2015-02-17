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
 * Image controller for the app
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_ImageController
  extends Mage_Core_Controller_Front_Action {

  public function getAction () {
    $request = $this->getRequest();

    if (!$request->has('file'))
        return;

    $fileName = $request->get('file');

    $dispretionPath
                  = Mage_Core_Model_File_Uploader::getDispretionPath($fileName);

    $fileName = $dispretionPath . DS . $fileName;

    $tokens = explode('.', $fileName);

    $type = $tokens[count($tokens) - 1];

    if ($type == 'jpg')
      $type = 'jpeg';

    $width = $request->has('width') && is_numeric($request->get('width'))
               ? (int) $request->get('width') : null;

    $height = $request->has('height') && is_numeric($request->get('height'))
                  ? (int) $request->get('height') : null;

    if ($width || $height)
      ///!!!TODO: check if resized image exists before resizing it
      $path = Mage::getModel('catalog/product_image')
        ->setDestinationSubdir('image')
        ->setKeepFrame(false)
        ->setConstrainOnly(true)
        ->setWidth($width)
        ->setHeight($height)
        ->setBaseFile($fileName)
        ->resize()
        ->saveFile()
        ->getNewFile();
    else
      $path = Mage::getModel('catalog/product_image')
        ->setBaseFile($fileName)
        ->getBaseFile();

    $this
      ->getResponse()
      ->setHeader('Pragma', '', true)
      ->setHeader('Expires', '', true)
      ->setHeader('Content-Type', 'image/' . $type , true)
      ->setHeader('Content-Length', filesize($path), true)
      ->setBody(file_get_contents($path));
  }
}
