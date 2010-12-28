<?php
/**
 * Filter Helper
 *
 * Generates a form to use to filter the pagination results of the current page
 *
 * @package default
 * @author Dean
 */
class BatchHelper extends Helper {
	var $helpers = array('Form');
	var $model = '';
	
	function create($model, $params = array()) {
		$this->model = $model;
		return $this->Form->create($model, $params);
	}

	function end() {
		return $this->Form->end();
	}

	/**
	 * Generates a filters form
	 *
	 * @param string $model 
	 * @param string $fields 
	 * @return void
	 * @author Dean
	 */
	function form($model, $fields = null, $blacklist = null) {
		$output = $this->Form->create($model, array('class' => 'filters'));
		$this->model = $model;
		if (!empty($fields)) {
			$cakeVersion = substr(Configure::read('Cake.version'), 0, 3);
			if ($cakeVersion === '1.2') {
				$output .= $this->_form12($fields);
			} else if ($cakeVersion === '1.3') {
				$output .= $this->_form13($fields, $blacklist);
			}
		}
		$output .= $this->Form->submit(__('Filter', true), array('name' => 'data[filter]'));
		$output .= $this->Form->submit(__('Reset', true), array('name' => 'data[reset]'));
		$output .= $this->Form->end();
		return $output;
	}

	/**
	 * Generates the form for CakePHP 1.2.x
	 *
	 * @param string $model 
	 * @param string $fields 
	 * @return void
	 * @author Dean
	 */
	function _form12($fields) {
		$output = '';
		foreach ($fields as $field => $options) {
			if (is_int($field)) {
				$field = $options;
				$options = array();
			}
			$output .= $this->_input($field, $options);
		}
		return $output;
	}

	/**
	 * Generates the form for CakePHP 1.3.x
	 *
	 * @param string $model 
	 * @param string $fields 
	 * @return void
	 * @author Dean
	 */
	function _form13($fields = null, $blacklist = null) {
		$fields = array_merge($fields, array(
			'legend' => false,
			'fieldset' => false,
		));
		return $this->Form->inputs($fields, $blacklist);
	}
	
	/**
	 * Generates a filtering row for use in a paginated table
	 *
	 * Pass null values in the fields array to generate empty header cells
	 * Pass true to force the filter/reset buttons to appear somewhere other than the end
	 * Example: $this->Batch->filter(array(null, 'name', 'date' => array('minYear' => 2000)))
	 *
	 * @param string $fields 
	 * @return void
	 * @author Dean
	 */
	function filter($fields = array()) {
		$output = '<tr class="filters">';

		if (!empty($fields)) {
			foreach ($fields as $field => $options) {
				if (is_int($field)) {
					$field = $options;
					$options = array();
				}
				if (empty($field)) {
					$output .= '<th>&nbsp;</th>';
				} elseif ($field === true) {
					$output .= '<th class="actions">';
					$output .= $this->Form->submit(__('Filter', true), array('div' => false, 'name' => 'data[filter]'));
					$output .= $this->Form->submit(__('Reset', true), array('div' => false, 'name' => 'data[reset]'));
					$output .= '</th>';
				} else {
					$options['group'] = 'Filter';
					$output .= '<th>' . $this->_input($field, $options, array('label' => false, 'div' => false)) . '</th>';
				}
			}
		}
		if (!in_array(true, $fields, true)) {
			$output .= '<th class="actions">';
			$output .= $this->Form->submit(__('Filter', true), array('div' => false, 'name' => 'data[filter]'));
			$output .= $this->Form->submit(__('Reset', true), array('div' => false, 'name' => 'data[reset]'));
			$output .= '</th>';
		}
		$output .= '</tr>';
		return $output;
	}

	/**
	 * Generates a filtering row for use in a paginated table
	 *
	 * Pass null values in the fields array to generate empty header cells
	 * Pass true to force the filter/reset buttons to appear somewhere other than the end
	 * Example: $this->Batch->filter(array(null, 'name', 'date' => array('minYear' => 2000)))
	 *
	 * @param string $fields 
	 * @return void
	 * @author Dean
	 */
	function batch($fields = array()) {
		$output = '<tr class="batch">';
		
		if (!empty($fields)) {
			foreach ($fields as $field => $options) {
				if (is_int($field)) {
					$field = $options;
					$options = array();
				}
				if (empty($field)) {
					$output .= '<th>&nbsp;</th>';
				} elseif ($field === true) {
					$output .= '<th class="actions">';
					$output .= $this->Form->submit(__('Update', true), array('div' => false, 'name' => 'data[update]'));
					$output .= $this->Form->submit(__('Delete', true), array('div' => false, 'name' => 'data[delete]'));
					$output .= '</th>';
				} else {
					$options['group'] = 'Batch';
					$output .= '<th>' . $this->_input($field, $options, array('label' => false, 'div' => false)) . '</th>';
				}
			}
		}
		if (!in_array(true, $fields, true)) {
			$output .= '<th class="actions">';
			$output .= $this->Form->submit(__('Update', true), array('div' => false, 'name' => 'data[update]'));
			$output .= $this->Form->submit(__('Delete', true), array('div' => false, 'name' => 'data[delete]', 'onclick' => "return confirm('Are you sure you want to delete the selected records?');"));
			$output .= '</th>';
		}
		$output .= '</tr>';
		return $output;
	}
	
	
	function checkbox($recordId) {
		$field = 'BatchRecords.' . $recordId;
		$params = array('value' => $recordId, 'hiddenField' => false);
		return $this->Form->checkbox($field, $params);
	}
	
	/**
	 * Generates a form input, allowing default options to be passed and handling cake versions separately
	 *
	 * @param string $model 
	 * @param string $field 
	 * @param array $options 
	 * @param array $defaults 
	 * @return string $output
	 * @author Dean
	 */
	protected function _input($field, $options = array(), $defaults = array()) {
		$position = strpos($field, '.');
		if ($position !== false) {
			$model = substr($field, 0, $position);
			$field = substr($field, $position + 1);
		} else {
			$model = $this->model;
		}
		$cakeVersion = substr(Configure::read('Cake.version'), 0, 3);
		if ($cakeVersion === '1.2') {
			if (
				isset($this->Form->fieldset['fields']["{$model}.{$field}"]['type'])
				&& $this->Form->fieldset['fields']["{$model}.{$field}"]['type'] = 'text'
			) {
				$options += array('type' => 'text');
			}
		} else if ($cakeVersion === '1.3') {
			if (
				isset($this->Form->fieldset[$model]['fields'][$field]['type'])
				&& $this->Form->fieldset[$model]['fields'][$field]['type'] == 'text'
			) {
				$options += array('type' => 'text');
			}
		}
		$output = $this->Form->input($options['group'] . '.' . $model . '.' . $field, array_merge($defaults, $options));
		return $output;
	}

}
