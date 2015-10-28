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
 * Controller for logs manipulation
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_Mventory_LogsController
  extends Mage_Adminhtml_Controller_Action
{
  const _CANT_CLEAR = <<<'EOT'
Can't clear log file "%s"
EOT;

  const _SUCCESS_CLEAR = <<<'EOT'
Log file "%s" was successfully cleared
EOT;

  const _NOT_FILE = <<<'EOT'
"%s" is not a file
EOT;

  /**
   * Pseudo-constructor
   */
  protected function _construct () {
    $this->setUsedModuleName('MVentory_API');
  }

  /**
   * Clear activity log
   *
   * @return MVentory_API_LogsController
   *   Instance of this class
   */
  public function clearAction () {
    $file = Mage::helper('mventory/imageclipper')->getLogFile();

    if (!(is_file($file) && (($handle = @fopen($file, 'w')) !== false)))
      return $this->_back($this->__(self::_CANT_CLEAR, $file), 'error');

    fclose($handle);

    return $this->_back($this->__(self::_SUCCESS_CLEAR, $file), 'success');
  }

  /**
   * Download activity log
   *
   * @return MVentory_API_LogsController
   *   Instance of this class
   */
  public function downloadAction () {
    $file = Mage::helper('mventory/imageclipper')->getLogFile();

    if (!is_file($file))
      return $this->_back($this->__(self::_NOT_FILE, $file), 'error');

    return $this->_prepareDownloadResponse(
      basename($file),
      array('type' => 'filename', 'value' => $file),
      'text/csv'
    );
  }

  /**
   * Return back to settings page with specified message
   *
   * @param string $msg
   *   Message to show on settings page
   *
   * @param string $type
   *   Type of message
   *
   * @return MVentory_API_LogsController
   *   Instance of this class
   */
  protected function _back ($msg, $type = 'notice') {
    Mage::getSingleton('adminhtml/session')->addMessage(
      Mage::getSingleton('core/message')->$type($msg)
    );

    return $this->_redirect(
      'adminhtml/system_config/edit',
      array(
        'section' => 'mventory',
        'website' => $this->getRequest()->getParam('website')
      )
    );
  }
}