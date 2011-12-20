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

/**
 * Starts a new form with modifications necessary for the batch plugin. 
 * Supports both filter and batch functions so that it can wrap an entire table.
 *
 * @param string $model 
 * @param string $params 
 * @param string $inputDefaults 
 * @return void
 * @author Dean Sofer
 */
	function create($model, $params = array(), $inputDefaults = array()) {
		$this->model = $model;
		if (!isset($params['class']))
			$params['class'] = 'batch';
		
		$params['url'] = '/' . (isset($this->params['url']['url'])?$this->params['url']['url']:$this->params['url']);
		
		$params['inputDefaults'] = array_merge(array(
			'empty' => __(' -- '),
			'div' => false,
			'label' => false,
		), $inputDefaults);
		return $this->Form->create($model, $params);
	}

/**
 * Simply closes the form. Additional functionality (if necessar) may be added later
 *
 * @return void
 * @author Dean Sofer
 */
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
			$fields = array_merge($fields, array(
				'legend' => false,
				'fieldset' => false,
			));
			$output .= $this->Form->inputs($fields, $blacklist);
		}
		$output .= $this->Form->submit(__('Filter'), array('name' => 'data[filter]'));
		$output .= $this->Form->submit(__('Reset'), array('name' => 'data[reset]'));
		$output .= $this->Form->end();
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
					$output = '<th class="actions">';
					$output .= $this->filterButtons();
					$output .= '</th>';
				} else {
					$options['group'] = 'Filter';
					$output .= '<th>' . $this->_input($field, $options) . '</th>';
				}
			}
		}
		if (!in_array(true, $fields, true)) {
			$output .= '<th class="actions">';
			$output .= $this->filterButtons();
			$output .= '</th>';
		}
		$output .= '</tr>';
		return $output;
	}
	
	/**
	 * Small helper function for filter method
	 *
	 * @return string
	 * @author Dean Sofer
	 */
	function filterButtons() {
		$output = $this->Form->submit(__('Filter'), array('div' => false, 'name' => 'data[Batch][filter]'));
		$output .= $this->Form->submit(__('Reset'), array('div' => false, 'name' => 'data[Batch][reset]'));
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
	function batch($fields = array(), $options = array()) {
		$options = array_merge(array(
		), $options);
		
		$output = '<tr class="batch">';
		
		if (!empty($fields)) {
			foreach ($fields as $field => $attributes) {
				if (is_int($field)) {
					$field = $attributes;
					$attributes = array();
				}
				if (empty($field)) {
					$output .= '<th>&nbsp;</th>';
				} elseif ($field === true) {
					$output = '<th class="actions">';
					$output .= $this->batchButtons();
					$output .= '</th>';
				} else {
					$attributes['group'] = 'Batch';
					if (!isset($attributes['disabled']))
						$attributes['disabled'] = true;
					$output .= '<th>';
					$output .= $this->Form->checkbox(null, array('name' => null, 'id' => null, 'checked' => !$attributes['disabled'], 'hiddenField' => false));
					$output .= $this->_input($field, $attributes);
					$output .= '</th>';
				}
			}
		}
		if (!in_array(true, $fields, true)) {
			$output .= '<th class="actions">';
			$output .= $this->batchButtons();
			$output .= '</th>';
		}
		$output .= '</tr>';
		return $output;
	}
	
	/**
	 * Small helper function for batch method
	 *
	 * @return string
	 * @author Dean Sofer
	 */
	function batchButtons() {
		$output = $this->Form->submit(__('Update'), array('div' => false, 'name' => 'data[Batch][update]', 'onclick' => "return confirm('".__('Are you sure you want to update the selected records?')."');"));
		$output .= $this->Form->submit(__('Delete'), array('div' => false, 'name' => 'data[Batch][delete]', 'onclick' => "return confirm('".__('Are you sure you want to delete the selected records?')."');"));
		return $output;
	}
	
	/**
	 * Generates a checkbox used for batch actions for the current row of items
	 *
	 * @param string $recordId 
	 * @return string
	 * @author Dean Sofer
	 */
	function checkbox($recordId) {
		$field = 'BatchRecords.' . $recordId;
		$params = array('value' => $recordId, 'hiddenField' => false, 'class' => 'batch');
		return $this->Form->checkbox($field, $params);
	}
	
	/**
	 * Generates a checkbox used only for toggling all batch checkboxes at once
	 *
	 * @return string
	 * @author Dean Sofer
	 */
	function all($options = array()) {
		$options = array_merge(array(
			'value' => false, 
			'id' => false,
			'name' => false,
			'hiddenField' => false, 
			'class' => 'batch-all'
		), $options);
		return $this->Form->checkbox('', $options);
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
		switch ($this->_fieldType($model, $field)) {
			case 'text':			
				$options += array('type' => 'text');
			break;
			case 'boolean':
				$options += array('options' => array(true => __('Yes'), false => __('No')));
			break;
		}
		$output = $this->Form->input($options['group'] . '.' . $model . '.' . $field, array_merge($defaults, $options));
		return $output;
	}
	
	/**
	 * Returns the field datatype based on the model schema
	 *
	 * @param string $model 
	 * @param string $field 
	 * @return string $type
	 * @author Dean
	 */
	protected function _fieldType($model, $field) {
		$type = null;
		if (isset($this->Form->fieldset[$model]['fields'][$field]['type'])) {
			$type = $this->Form->fieldset[$model]['fields'][$field]['type'];
		}
		return $type;
	}

}
