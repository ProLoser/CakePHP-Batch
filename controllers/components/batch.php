<?php
/**
 * BatchComponent
 * 
 * [Short Description]
 *
 * @package default
 * @author Dean
 * @version $Id$
 * @copyright 
 **/

class BatchComponent extends Object {

	var $_settings = array();

	/**
	 * Called before the Controller::beforeFilter().
	 *
	 * @param object  A reference to the controller
	 * @return void
	 * @access public
	 * @link http://book.cakephp.org/view/65/MVC-Class-Access-Within-Components
	 */
	function initialize(&$controller, $settings = array()) {
		$this->_controller = $controller;
		if (!isset($this->_settings[$controller->name])) {
			$this->_settings[$controller->name] = $settings;
			$this->_controller = $controller;
		}
	}

	/**
	 * Called after the Controller::beforeFilter() and before the controller action
	 *
	 * @param object  A reference to the controller
	 * @return void
	 * @access public
	 * @link http://book.cakephp.org/view/65/MVC-Class-Access-Within-Components
	 */
	function startup(&$controller) {
	}

	/**
	 * Called after the Controller::beforeRender(), after the view class is loaded, and before the
	 * Controller::render()
	 *
	 * @param object  A reference to the controller
	 * @return void
	 * @access public
	 */
	function beforeRender(&$controller) {
		$controller->helpers['Batch.Batch'] = $this->_settings;
	}
	
	public function delete($data, $cascade = true, $callbacks = false) {
		$conditions = array(
			'id' => $data[$this->_controller->modelClass],
		);
		$this->_controller->{$this->_controller->modelClass}->deleteAll($conditions, $cascade = true, $callbacks = false);
	}
	
	public function update($data, $fields) {
		$conditions = array(
			'id' => $data[$this->_controller->modelClass],
		);
		$this->_controller->{$this->_controller->modelClass}->updateAll($fields, $conditions = true);
	}
}
?>