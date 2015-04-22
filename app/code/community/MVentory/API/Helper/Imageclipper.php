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
 * Image helper
 *
 * @package MVentory/API
 * @author Bogdan
 */
class MVentory_API_Helper_Imageclipper extends MVentory_API_Helper_Data
{

  protected $_csvHeaders = array(
    'date' => 'Date',
    'time' => 'Time',
    'event' => 'Event',
    'file' => 'File',
    'sku' => 'sku'
  );

  protected $_logfile = 'var/log/imglog.csv';

  /**
   * Download and copy image to media/catalog/product/i/m/imag1.jpg
   *
   * @param image Dropbox fullpath "/wmc/to/imag1.jpg"
   * @param int   new size in bytes
   * @return bool
   */
  public function copyToMedia ($dropboxFile, $bytes) {
    $image = basename($dropboxFile);
    $dispretionPath = Varien_File_Uploader::getDispretionPath($image);
    $imgDst = Mage::getBaseDir('media')
              . DS . 'catalog'
              . DS . 'product'
              . $dispretionPath
              . DS . $image;

    if (!file_exists($imgDst))
      return false;

    if (filesize($imgDst) == $bytes)
      return false;

    $ids = $this->getProductIdsByImage($image);
    $skus = array();

    foreach ($ids as $id)
      $skus[] = Mage::getModel('catalog/product')
        ->load($id)
        ->getSku();

    $skus = implode(', ', $skus);

    try {
      $fd = fopen($imgDst, 'cb');
      $md = $this->getDbxClient()->getFile($dropboxFile, $fd);

      if (ftell($fd) == $md['bytes']) {
        fflush($fd);
        ftruncate($fd, ftell($fd));
      }

      fclose($fd);
    } catch (Exception $e) {
      Mage::throwException($e);

      $this->log(array(
        'date' => $this->getCurrentDate(),
        'time' => $this->getCurrentTime(),
        'event' => 'image-download-failed',
        'file' => $image,
        'sku'  => $skus
      ));

      return false;
    }

    //Clear thumbnails
    $cacheDir = Mage::getBaseDir('media')
                . DS . 'catalog'
                . DS . 'product'
                . DS . 'cache';

    $iteratorDir = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($cacheDir)
    );

    $iteratorRegex = new RegexIterator(
      $iteratorDir,
      "/$image/",
      RecursiveRegexIterator::GET_MATCH
    );

    foreach ($iteratorRegex as $filepath => $fileinfo)
      if (is_file($filepath))
        @unlink($filepath);

    $this->log(array(
      'date' => $this->getCurrentDate(),
      'time' => $this->getCurrentTime(),
      'event' => 'image-download',
      'file' => $image,
      'sku'  => $skus
    ));

    return true;
  }

  /**
   * This will remove the exclude flag
   * on all products that have img1.jpg in gallery.
   *
   * @param string $image img1.jpg
   */
  public function removeExcludeFlag ($image) {
    Mage::app()
      ->setCurrentStore(Mage::getModel('core/store')
      ->load(Mage_Core_Model_App::ADMIN_STORE_ID));

    $dispretionPath = Varien_File_Uploader::getDispretionPath($image);
    $imagePath = $dispretionPath.DS.$image;

    $prodIds = $this->getProductIdsByImage($image);

    $api = Mage::getModel('mventory/product_attribute_media_api');

    foreach ($prodIds as $_id) {
      $file = $imagePath;
      $data = array('disabled' => '0');
      $api->update($_id, $file, $data );

      $this->setProductVisibility($_id);
    }
  }

  /**
   * @param $event array
   */
  public function log ($event) {
    $file = $this->getLogFile();

    $io = new Varien_Io_File();
    $io->open(array('path' => pathinfo($file, PATHINFO_DIRNAME)));
    $io->streamOpen($file, 'a+');

    if (!$io->fileExists($file) || filesize($file) <= 1)
      $io->streamWriteCsv($this->_csvHeaders);

    $row = array();

    foreach ($this->_csvHeaders as $colName => $v)
      $row[$colName] = $event[$colName];

    $io->streamWriteCsv($row);
  }

  public function getCurrentDate () {
    return Mage::getModel('core/date')->date(
      'd/m/Y',
      Mage::getModel('core/date')->timestamp(time())
    );
  }

  public function getCurrentTime () {
    return Mage::getModel('core/date')->date(
      'H:i:s',
      Mage::getModel('core/date')->timestamp(time())
    );
  }

  public function getLogFile () {
    return Mage::getBaseDir() . DS . $this->_logfile;
  }

  /**
   * @param $imgname "img1.jpg"
   * @param $excludedFromGallery bool Get only products with this image excluded
   * @return array
   */
  public function getProductIdsByImage ($imgName, $excludedOnly = false) {
    $dispretionPath = Varien_File_Uploader::getDispretionPath($imgName);
    $imagePath = $dispretionPath . DS . $imgName;
    $coreResource = Mage::getSingleton('core/resource');
    $mgTable = $coreResource
      ->getTableName('catalog/product_attribute_media_gallery');
    $mgvTable = $coreResource
      ->getTableName('catalog/product_attribute_media_gallery_value');
    $coll = Mage::getModel('catalog/product')->getCollection();
    $coll->joinField(
      'mg',
      $mgTable,
      'value',
      'entity_id=entity_id',
      "{{table}}.value = '" . $imagePath . "'"
    );

    $coll
      ->getSelect()
      ->joinLeft(
        array('mgv' => $mgvTable),
        'at_mg.value_id = mgv.value_id',
        array('excluded' => 'mgv.disabled')
      );

    if ($excludedOnly)
      $coll
        ->getSelect()
        ->where('mgv.disabled = ?', '1' );

    $coll->load();

    return array_map(
      function ($el) { return $el['entity_id']; },
      $coll->toarray()
    );
  }

  /**
   * Make sure that this file is an image
   *
   * @param string filepath
   * @return bool Return true if known img type
   */
  public function isImage ($file) {
    if (!file_exists($file))
      return false;

    $imgTypes = array(
      IMAGETYPE_JPEG,
      IMAGETYPE_PNG,
      IMAGETYPE_GIF,
      IMAGETYPE_BMP
    );

    $type = @exif_imagetype($file);

    return in_array($type, $imgTypes);
  }

  /**
   * @return string /full/path/magento/media/backup
   */
  public function getBackupFolder () {
    $confVal = Mage::getStoreConfig(MVentory_API_Model_Config::_BGG_BACKUP_DIR);

    return $confVal ? Mage::getBaseDir('media') . DS . $confVal : null;
  }

  /**
   * @param $pId product Id
   */
  public function setProductVisibility ($pId) {
    Mage::app()
      ->setCurrentStore(Mage::getModel('core/store')
      ->load(Mage_Core_Model_App::ADMIN_STORE_ID));

    //If this is a child product, keep invisible.
    $parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')
      ->getParentIdsByChild($pId);

    //Set visibility to Catalog,Search
    if (empty($parentIds)) {
      Mage::getModel('catalog/product')
        ->load($pId)
        ->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
        ->save();
    }
  }

  /**
   * Check if supplied file is member of the dropbox web folder from settings
   *
   * @param string $file
   *   Path to file on dbx
   *
   * @return bool
   *   True if the file is member of the dropbox web folder
   */
  public function isFileInWebFolder ($file) {
    return Mage::helper('mventory/string')->startsWith(
      strtolower(trim($file)),
      strtolower(trim(Mage::getStoreConfig(
        MVentory_API_Model_Config::_BGG_DBX_PATH
      )))
    );
  }

  /**
   * @return Dropbox\Client
   */
  public function getDbxClient () {
    return new Dropbox\Client(
      Mage::getStoreConfig(MVentory_API_Model_Config::_BGG_DBX_TKN),
      'PHP-Example/1.0'
    );
  }

  /**
   * Upload to Dropbox
   *
   * @param string /path/to/img1.jpg
   * @param string imgdata
   * @return array meta-data
   */
  public function uploadFileFromString ($filename, $data) {
    $dbxClient = $this->getDbxClient();

    return $dbxClient->uploadFileFromString(
      $this->_toDbxPath($filename),
      Dropbox\WriteMode::force(),
      $data
    );
  }

  /**
  * Delete file from Dropbox
  *
  * @param string /path/to/img1.jpg
  * @return bool|array meta-data
  */
  public function deleteFromDropbox ($filename) {
    $dbxClient = $this->getDbxClient();

    try {
      return $dbxClient->delete($this->_toDbxPath($filename));
    } catch (Exception $e) {
    }

    return false;
  }

  /**
   * Convert file name to full dropbox path
   *
   * @param string $filename
   *   File name
   *
   * @return string
   *   Full dropbox path
   */
  protected function _toDbxPath ($filename) {
    return Mage::getStoreConfig(MVentory_API_Model_Config::_BGG_DBX_PATH)
           . $filename;
  }
}
