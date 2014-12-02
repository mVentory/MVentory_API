<?php

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License BY-NC-ND.
 * NonCommercial — You may not use the material for commercial purposes.
 * NoDerivatives — If you remix, transform, or build upon the material,
 * you may not distribute the modified material.
 * See the full license at http://creativecommons.org/licenses/by-nc-nd/4.0/
 *
 * See http://mventory.com/legal/licensing/ for other licensing options.
 *
 * @package MVentory/API
 * @copyright Copyright (c) 2014 mVentory Ltd. (http://mventory.com)
 * @license http://creativecommons.org/licenses/by-nc-nd/4.0/
 */

/**
 * Controller for category mapping rules
 *
 * @package MVentory/API
 * @author Anatoly A. Kazantsev <anatoly@mventory.com>
 */
class MVentory_API_MatchingController
  extends Mage_Adminhtml_Controller_Action {

  protected function _construct() {
    $this->setUsedModuleName('MVentory_API');
  }

  /**
   * Append rule action
   *
   * @return MVentory_API_MatchingController
   */
  public function appendAction () {
    $request = $this->getRequest();

    $setId =  $request->getParam('set_id');
    $rule = $request->getParam('rule');

    if (!($setId && $rule))
      return $this->_error('No set_id or rule parameters');

    try {
      $rule = Mage::helper('core')->jsonDecode($rule);

      Mage::getModel('mventory/matching')
        ->loadBySetId($setId, false)
        ->append($rule)
        ->save();

      $this->_success('Rule saved');
    } catch(Exception $e) {
      $this->_error('Failed to save rule', $e);
    }

    return $this;
  }

  /**
   * Remove rule action
   *
   * @return MVentory_API_MatchingController
   */
  public function removeAction () {
    $request = $this->getRequest();

    $setId = $request->getParam('set_id');
    $ruleId = $request->getParam('rule_id');

    if (!($setId && $ruleId))
      return $this->_error('No set_id or rule parameters');

    try {
      Mage::getModel('mventory/matching')
        ->loadBySetId($setId, false)
        ->remove($ruleId)
        ->save();

      $this->_success('Rule removed');
    } catch(Exception $e) {
      $this->_error('Failed to remove rule', $e);
    }

    return $this;
  }

  /**
   * Reorder rules action
   */
  public function reorderAction () {
    $request = $this->getRequest();

    $setId = $request->getParam('set_id');
    $ids = $request->getParam('ids');

    if (!($setId && $ids))
      return $this->_error('No set_id or ids parameters');

    try {
      Mage::getModel('mventory/matching')
        ->loadBySetId($setId, false)
        ->reorder($ids)
        ->save();

      $this->_success('Rules reordered');
    } catch(Exception $e) {
      $this->_error('Failed to reorder rules', $e);
    }

    return $this;
  }

  /**
   * Prepare successfull response
   *
   * @param string $msg Response message
   * @param array $data Additional response data
   * @return MVentory_API_MatchingController
   */
  protected function _success ($msg, $data = null) {
    return $this->_response(true, $msg, $data);
  }

  /**
   * Prepare error response
   *
   * @param string $msg Response message
   * @param Exception $exception Exception
   * @param array $data Additional response data
   * @return MVentory_API_MatchingController
   */
  protected function _error ($msg, $exception = null, $data = array()) {
    if ($exception)
      $data['exception'] = array(
        'message' => $exception->getMessage(),
        'trace' => $exception->getTraceAsString()
      );

    return $this->_response(false, $msg, $data);
  }

  /**
   * Prepare response in JSON format
   *
   * @param bool $success Was request successful
   * @param string $msg Response message
   * @param array $data Additional response data
   * @return MVentory_API_MatchingController
   */
  protected function _response ($success, $msg, $data = array()) {
    echo Mage::helper('core')->jsonEncode(array(
      'success' => $success,
      'message' => $this->__($msg),
      'data' => $data
    ));

    return $this;
  }
}
