<?php
/**
 * Batch component
 *
 */

App::uses('Sanitize', 'Utility');
class BatchComponent extends Component {

/**
 * Default Component::$params
 *
 * actions:				Actions upon which this component will act upon
 * defaults:			Holds pagination defaults for controller actions.
 * fieldFormatting:		Fields which will replace the regular syntax in where i.e. field = 'value'
 * formOptionsDatetime:	Formatting for datetime fields (unused)
 * paginatorParams:		Paginator params sent in the URL
 * parsed:				Used to tell whether the data options have been parsed
 * redirect:			Used to tell whether to redirect so the url includes filter data
 * useTime:				Used to tell whether time should be used in the filtering
 * separator:			Separator to use between fields in a date input
 * rangeSeparator:		Separator to use between dates in a date range
 * url:					Url variable used in paginate helper (array('url'=>$url));
 * whitelist:			Array of fields and models for which this component may filter
 *
 * @var array
 */
	var $defaults = array(
		'actions' => array('index'),
		'defaults' => array(),
		'fieldFormatting' => array(
			'string'	=> "LIKE '%%%s%%'",
			'text'		=> "LIKE '%%%s%%'",
			'datetime'	=> "LIKE '%%%s%%'"
		),
		'formOptionsDatetime' => array(),
		'paginatorParams' => array(
			'page',
			'sort',
			'direction',
			'limit'
		),
		'parsed' => false,
		'redirect' => false,
		'useTime' => false,
		'separator' => '/',
		'rangeSeparator' => '-',
		'url' => array(),
		'whitelist' => array(),
		'cascade' => true,
		'callbacks' => false,
		'security' => true,
	);

/**
 * Pagination array for component
 *
 * @var array
 */
	public $paginate = array('conditions' => array());

/**
 * Holds filterOptions for 1.2 Compatibility
 *
 * @var array
 **/
	protected $filterOptions = array();

/**
 * Stores data for the current pagination set
 *
 * @var array
 * @access private
 **/
	protected $data = array();

	protected $controller;

	public function shutdown(Controller $controller) {}

	public function beforeRender(Controller $controller) {}

	public function beforeRedirect(Controller $controller, $url, $status = null, $exit = true) {}

/**
 * Constructor
 *
 * @param ComponentCollection $collection A ComponentCollection this component can use to lazy load its components
 * @param array $settings Array of configuration settings.
 */
	public function __construct(ComponentCollection $collection, $settings = array()) {
		$this->settings = array_merge($this->defaults, $settings);
	}

/**
 * Startup callback
 *
 * @param string $controller
 * @return void
 * @author Dean Sofer
 */
	function startup(Controller $controller) {
		if (in_array($controller->request->action, $this->settings['actions'])) {
			$this->controller = $controller;
			$controller->helpers[] = 'Batch.Batch';
			// Fix for security component
			if ($this->settings['security'] && in_array('Security', array_keys($controller->components), true)) {
				$controller->Security->disabledFields = array_merge($controller->Security->disabledFields, array(
					'Batch.filter',
					'Batch.reset',
					'Batch.clear',
					'Batch.delete',
					'Batch.update',
				));
			}
			$this->data = $controller->request->data;
			$this->paginate = array_merge($this->paginate, $controller->paginate);
			if (isset($this->data['Filter']['reset']) || isset($this->data['Filter']['cancel'])) {
				$controller->request->data = array();
			} else {
				$this->_prepareFilters();
				$this->_processFilters();
				$this->_processBatch();

				foreach ($this->settings['url'] as $key => $value) {
					$controller->request->params['named'][$key] = $value;
				}
				$this->filterOptions = array('url' => array_diff(
					$controller->request->params['named'],
					array('page' => 1, 'limit' => 20, 'sort' => 'val')
				));

				$this->settings['formOptionsDatetime'] = array(
					'dateFormat' => 'DMY',
					'empty' => '-',
					'maxYear' => date("Y"),
					'minYear' => date("Y")-2,
					'type' => 'date'
				);
			}
			$this->controller->paginate = $this->paginate;

		}
	}

/**
 * Builds up a selected datetime for the form helper
 *
 * @param string $fieldname the name of the field to process
 * @return null|string
 */
	function _processDatetime($fieldname) {
		if (isset($this->params['named'][$fieldname])) {
			$exploded = explode('-', $this->params['named'][$fieldname]);
			if (!empty($exploded)) {
				$datetime = '';
				foreach ($exploded as $k => $e) {
					$datetime = (empty($e)) ? (($k == 0) ? '0000' : '00') : $e;
					if ($k != 2) $datetime .= '-';
				}
			}
		}
		return $datetime;
	}

/**
 * undocumented function
 *
 * @return void
 * @author Dean Sofer
 */
	function _processBatch() {
		if (isset($this->data['Batch']) && isset($this->data['BatchRecords'])) {
			$rows = $this->data['BatchRecords'];
			if (!$rows && (isset($this->data['Batch']['delete']) || isset($this->data['Batch']['update']))) {
				$this->controller->Session->setFlash(__('No rows selected'));
			} elseif (isset($this->data['Batch']['delete'])) {
				$this->_batchDelete($rows);
			} elseif (isset($this->data['Batch']['update'])) {
				unset($this->data['Batch']['update']);
				$this->_batchUpdate($rows);
			}
			unset($this->controller->request->data['Batch']);
			unset($this->controller->request->data['BatchRecords']);
		}
	}

	function _batchDelete($rows) {
		if ($this->controller->{$this->controller->modelClass}->deleteAll(array($this->controller->modelClass . '.id' => $rows), $this->settings['cascade'], $this->settings['callbacks'])) {
			$this->controller->Session->setFlash(sprintf(__('%s record(s) successfully deleted'), count($rows)));
		} else {
			$this->controller->Session->setFlash(__('There was an error attempting to delete the specified rows'));
		}
	}

	function _batchUpdate($rows) {
		$data = $this->data['Batch'];
		foreach ($data as $model => $fields) {
			$fields = $this->_escapeFields($fields, $model);
			if (isset($this->controller->{$model})) {
				$this->controller->{$model}->updateAll($fields, array($model . '.' . $this->controller->{$model}->primaryKey => $rows));
			} elseif (isset($this->controller->{$this->controller->modelClass}->belongsTo[$model])) {
				$foreignId = $this->controller->{$this->controller->modelClass}->belongsTo[$model]['foreign_id'];
				$this->controller->{$this->controller->modelClass}->updateAll($fields, array($this->controller->modelClass . '.' . $foreignId => $rows));
			} elseif (isset($this->controller->{$this->controller->modelClass}->hasOne[$model])) {
				$foreignId = $this->controller->{$this->controller->modelClass}->hasOne[$model]['foreign_id'];
				$this->controller->{$this->controller->modelClass}->{$model}->updateAll($fields, array($model . '.' . $foreignId => $rows));
			}
		}
		$this->controller->Session->setFlash(sprintf(__('%s record(s) successfully updated'), count($rows)));
	}

/**
 * Function which will change controller->request->data array
 *
 * @return void
 * @access public
 */
	function _processFilters() {

		// Set default filter values
		$this->data['Filter'] = array_merge($this->settings['defaults'], $this->data['Filter']);
		$redirectData = array();
		if (isset($this->data['Filter']['filter'])) {
			foreach ($this->data['Filter'] as $model => $fields) {
				$modelFieldNames = array();
				if (isset($this->controller->{$model})) {
					$modelFieldNames = $this->controller->{$model}->getColumnTypes();
				} else if (isset($this->controller->{$this->controller->modelClass}->belongsTo[$model]) || isset($this->controller->{$this->controller->modelClass}->hasOne[$model])) {
					$modelFieldNames = $this->controller->{$this->controller->modelClass}->{$model}->getColumnTypes();
				}
				if (!empty($modelFieldNames)) {
					foreach ($fields as $filteredFieldName => $filteredFieldData) {
						$this->_filterField($model, $filteredFieldName, $filteredFieldData, $modelFieldNames);
					}
				} else {
					if (isset($this->controller->{$this->controller->modelClass}->hasMany[$model])) {
						$modelFieldNames = $this->controller->{$this->controller->modelClass}->{$model}->getColumnTypes();
						if (!empty($modelFieldNames)) {
							foreach ($fields as $filteredFieldName => $filteredFieldData) {
								$this->_filterField($model, $filteredFieldName, $filteredFieldData, $modelFieldNames);
							}
						}
					} else if (isset($this->controller->{$this->controller->modelClass}->hasAndBelongsToMany[$model])) {
						$modelFieldNames = $this->controller->{$this->controller->modelClass}->{$model}->getColumnTypes();
						if (!empty($modelFieldNames)) {
							foreach ($fields as $filteredFieldName => $filteredFieldData) {
								$this->_filterField($model, $filteredFieldName, $filteredFieldData, $modelFieldNames);
							}
						}
					}
				}
				// Save model data for redirect
				if ($this->settings['redirect'] && is_array($this->data['Filter'][$model])) {
					foreach ($this->data['Filter'][$model] as $key => $val) {
						$redirectData["$model.$key"] = $val;
					}
				}
				// Unset empty model data
				if (count($fields) == 0) {
					unset($this->data['Filter'][$model]);
				}
			}
		}
		// TODO Really need to relocate this code to a dedicated 'redirect' method that can be called from elsewhere too
		// If redirect has been set true, and the data had not been parsed before and put into the url, does it now
		if ($this->settings['parsed'] === false && $this->settings['redirect'] === true) {
			$this->settings['url'] = "/Filter.parsed:true/{$this->_buildNamedParams($redirectData)}";
			$this->controller->redirect("/{$this->controller->name}/index{$this->settings['url']}");
		}
	}

/**
 * Builds a named parameter list
 *
 * @param array $params An array of parameters to parse
 * @return string Parsed string of named parameters
 * @access private
 * @author Chad Jablonski
 **/
	function _buildNamedParams($params) {
		$paramString = '';

		foreach ($params as $key => $value) {
			$value = urlencode($value);
			$paramString .= "{$key}:{$value}/";
		}

		return $paramString;
	}


/**
 * Filters an individual field
 *
 * @param string $model name of model
 * @param string $filteredFieldName
 * @param string|array $filteredFieldName
 * @param array $modelFieldNames
 * @return array
 * @access private
 * @author Jose Diaz-Gonzalez
 **/
	function _filterField($model, $filteredFieldName, $filteredFieldData, $modelFieldNames = array()) {
		if (is_array($filteredFieldData)) {
			if (!isset($modelFieldNames[$filteredFieldName])) {
				if ($this->_arrayHasKeys($filteredFieldData, array('year', 'month', 'day'))) {
					$filteredFieldData = "{$filteredFieldData['month']}{$this->settings['separator']}{$filteredFieldData['day']}{$this->settings['separator']}{$filteredFieldData['year']}";
				}
			} else if ($modelFieldNames[$filteredFieldName] == 'datetime') {
				$filteredFieldData = $this->_prepareDatetime($filteredFieldData);
			}
		}

		if ($filteredFieldData != '') {
			if ((isset($this->settings['whitelist'][$model]) && is_array($this->settings['whitelist'][$model]) && !in_array('*', $this->settings['whitelist'][$model]) && !in_array($filteredFieldName, $this->settings['whitelist'][$model])) || (!isset($this->settings['whitelist'][$model]) && !empty($this->settings['whitelist']))) {
				return;
			}
			if (substr($filteredFieldName, 0, 5) == 'FROM_') {
				$filteredFieldName = substr($filteredFieldName, 5);
				$pieces = explode($this->settings['separator'], $filteredFieldData);
				$this->paginate['conditions']["{$model}.{$filteredFieldName} >="] = "{$pieces[2]}/{$pieces[0]}/{$pieces[1]}";
			} else if (substr($filteredFieldName, 0, 3) == 'TO_') {
				$filteredFieldName = substr($filteredFieldName, 3);
				$pieces = explode($this->settings['separator'], $filteredFieldData);
				$this->paginate['conditions']["{$model}.{$filteredFieldName} <="] = "{$pieces[2]}/{$pieces[0]}/{$pieces[1]}";
			} else if (substr($filteredFieldName, 0, 6) == 'RANGE_') {
				$filteredFieldName = substr($filteredFieldName, 6);
				$pieces = explode($this->settings['rangeSeparator'], $filteredFieldData);
				$startDate = date('Y/m/d', strtotime($pieces[0]));
				if (count($pieces) == 1) {
					$this->paginate['conditions']["{$model}.{$filteredFieldName}"] = $startDate;
				} else {
					$this->paginate['conditions']["{$model}.{$filteredFieldName} >="] = $startDate;
					$endDate = date('Y/m/d', strtotime($pieces[1]));
					$this->paginate['conditions']["{$model}.{$filteredFieldName} <="] = $endDate;
				}
			} else if (isset($modelFieldNames[$filteredFieldName]) && isset($this->settings['fieldFormatting'][$modelFieldNames[$filteredFieldName]])) {
				// insert value into fieldFormatting
				$tmp = sprintf($this->settings['fieldFormatting'][$modelFieldNames[$filteredFieldName]], $filteredFieldData);
				// don't put key.fieldname as array key if a LIKE clause
				if (substr($tmp, 0, 4) == 'LIKE') {
					$this->paginate['conditions']["{$model}.{$filteredFieldName} LIKE"] = "%{$filteredFieldData}%";
				} else {
					$this->paginate['conditions']["{$model}.{$filteredFieldName}"] = $tmp;
				}
			} else if (isset($modelFieldNames[$filteredFieldName])) {
				$this->paginate['conditions']["{$model}.{$filteredFieldName}"] = $filteredFieldData;
			}
			$this->settings['url']["{$model}.{$filteredFieldName}"] = $filteredFieldData;
		}
	}

/**
 * Store sanitized version of filter data
 *
 * @param object $this->controller Reference to controller
 * @access private
 */
	function _prepareFilters() {
		if (isset($this->controller->request->data['Batch'])) {
			foreach ($this->data['Filter'] as $model => $fields) {
				if (is_array($fields)) {
					foreach ($fields as $key => $field) {
						if ($field == '') {
							unset($this->data['Filter'][$model][$key]);
						}
					}
				}
			}
			$sanitize = new Sanitize();
			$this->data['Filter'] = $sanitize->clean($this->data['Filter'], array('encode' => false));
		}
		if (empty($this->data['Filter'])) {
			$this->data['Filter'] = $this->_checkParams($this->controller);
		}
	}

/**
 * Parses named parameters from the current GET request
 *
 * @param object $this->controller Reference to controller
 * @return array Parsed params
 * @access private
 */
	function _checkParams() {
		if (empty($this->controller->request->params['named'])) {
			$filter = array();
		}

		App::uses('Sanitize', 'Utility');
		$sanitize = new Sanitize();

		$this->controller->request->params['named'] = $sanitize->clean($this->controller->request->params['named'], array('encode' => false));
		if (isset($this->controller->request->params['named']['Filter.parsed'])) {
			if ($this->controller->request->params['named']['Filter.parsed']) {
				$this->settings['parsed'] = true;
				$filter = array();
			}
		}

		foreach ($this->controller->request->params['named'] as $field => $value) {
			if (!in_array($field, $this->settings['paginatorParams']) && $field != 'Filter.parsed') {
				$fields = explode('.', $field);
				if (sizeof($fields) == 1) {
					$filter[$this->controller->modelClass][$field] = $value;
				} else {
					$filter[$fields[0]][$fields[1]] = $value;
				}
			}
		}

		if (!empty($filter)) {
			$filter['filter'] = true;
			return $filter;
		} else {
			return array();
		}
	}

/**
 * Prepares a date array for a MySQL WHERE clause
 *
 * @param array $date
 * @return string
 * @access private
 * @author Jeffrey Marvin
 */
	function _prepareDatetime($date) {
		if ($this->settings['useTime'] === true) {
			return  "{$date['year']}-{$date['month']}-{$date['day']}"
				. ' ' . (($date['meridian'] == 'pm' && $date['hour'] != 12) ? $date['hour'] + 12 : $date['hour'])
				. ':' . (($date['min'] < 10) ? "0{$date['min']}" : $date['min']);
		} else {
			return "{$date['year']}-{$date['month']}-{$date['day']}";
		}
	}

/**
 * Checks if all keys are held within an array
 *
 * @param array $array
 * @param array $keys
 * @param boolean $size
 * @return boolean array has keys, optional check on size of array
 * @access private
 * @author Jose Diaz-Gonzalez
 **/
	function _arrayHasKeys($array, $keys, $size = null) {
		if (count($array) != count($keys)) return false;

		$array = array_keys($array);
		foreach ($keys as $key) {
			if (!in_array($key, $array)) {
				return false;
			}
		}
		return true;
	}

/**
 * Escapes all the values of the fields and properly sets them up into a updateAll friendly array
 *
 * @param array $fields
 * @return array $fields
 * @author Dean Sofer
 */
	function _escapeFields($fields, $model) {
		$dbo = $this->controller->{$model}->getDataSource();
		foreach ($fields as $field => $value) {
			$fields[$field] = $dbo->value($value, $field, false);
		}
		return $fields;
	}
}