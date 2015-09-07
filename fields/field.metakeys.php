<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	/**
	 * @package field_metakeys
	 */
	require_once FACE . '/interface.exportablefield.php';
	require_once FACE . '/interface.importablefield.php';

	Class fieldMetaKeys extends Field implements ImportableField, ExportableField {

		public function __construct() {
			parent::__construct();
			$this->_name = __('Meta Keys');
			$this->_required = true;

			$this->set('required', 'no');
			$this->set('show_column', 'no');
			$this->set('location', 'sidebar');
		}

	/*-------------------------------------------------------------------------
		Setup:
	-------------------------------------------------------------------------*/

		public function createTable() {
			try {
				Symphony::Database()->query(sprintf("
						CREATE TABLE IF NOT EXISTS `tbl_entries_data_%d` (
							`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
							`entry_id` INT(11) UNSIGNED NOT NULL,
							`key_handle` VARCHAR(255) NULL,
							`key_value` TEXT NULL,
							`value_handle` VARCHAR(255) DEFAULT NULL,
							`value_value` TEXT NULL,
							PRIMARY KEY (`id`),
							KEY `entry_id` (`entry_id`)
						) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
					", $this->get('id')
				));

				return true;
			}
			catch (Exception $ex) {
				return false;
			}
		}

		public function canFilter(){
			return true;
		}

		public function prePopulate(){
			return false;
		}

		public function allowDatasourceParamOutput(){
			return true;
		}

	/*-------------------------------------------------------------------------
		Utilities:
	-------------------------------------------------------------------------*/

		public function applyValidationRules($data) {
			$rule = $this->get('validator');

			return ($rule ? General::validateString($data['value'], $rule) : true);
		}

		public function buildPair($key = null, $value = null, $i = -1) {
			$element_name = $this->get('element_name');

			$li = new XMLElement('li');
			if($i == -1) {
				$li->setAttribute('class', 'template');
			}

			// Header
			$header = new XMLElement('header');
			$label = !is_null($key) ? $key : __('New Pair');
			$header->setAttribute('data-name', 'pair');
			$header->appendChild(new XMLElement('h4', '<strong>' . $label . '</strong>'));
			$li->appendChild($header);

			// Key
			$label = Widget::Label();
			$label->appendChild(
				Widget::Input(
					"fields[$element_name][$i][key]", General::sanitize($key), 'text', array('placeholder' => __('Key'))
				)
			);
			$li->appendChild($label);

			// Value
			$label = Widget::Label();
			$label->appendChild(
				Widget::Input(
					"fields[$element_name][$i][value]", General::sanitize($value), 'text', array('placeholder' => __('Value'))
				)
			);
			$li->appendChild($label);

			return $li;
		}

	/*-------------------------------------------------------------------------
		Settings:
	-------------------------------------------------------------------------*/

		/**
		 * Displays setting panel in section editor.
		 *
		 * @param XMLElement $wrapper - parent element wrapping the field
		 * @param array $errors - array with field errors, $errors['name-of-field-element']
		 */
		public function displaySettingsPanel(XMLElement &$wrapper, $errors = null) {
			// Initialize field settings based on class defaults (name, placement)
			parent::displaySettingsPanel($wrapper, $errors);

			$order = $this->get('sortorder');

			$group = new XMLElement('div');
			$group->setAttribute('class', 'two columns');

			// Default Keys
			$label = Widget::Label(__('Default Keys'));
			$label->setAttribute('class', 'column');
			$label->appendChild(
				new XMLElement('i', __('Optional'))
			);
			$label->appendChild(Widget::Input(
				"fields[{$order}][default_keys]", $this->get('default_keys')
			));
			$label->appendChild(
				new XMLElement('p', __('You can optionally assign values by using a double colon: %s.', array('<code>' . __('key::value') . '</code>')) . '<br />' . __('If you want to use a comma in your key or value, you need to escape it, e. g. %s.', array('<code>' . __('Red\\, Green or Blue') . '</code>')), array('class' => 'help'))
			);

			$group->appendChild($label);

			// Validator
			$div = new XMLElement('div');
			$div->setAttribute('class', 'column');
			$this->buildValidationSelect(
				$div, $this->get('validator'), "fields[{$order}][validator]"
			);
			// Remove 'column' from `buildValidationSelect`
			$div->getChild(0)->setAttribute('class', '');

			$group->appendChild($div);
			$wrapper->appendChild($group);

			// Default options
			$div = new XMLElement('div', null, array('class' => 'two columns'));
			$this->appendRequiredCheckbox($div);
			$this->appendShowColumnCheckbox($div);

			// Automatic delete
			$label = Widget::Label(null, null, 'column');
			$input = Widget::Input('fields['.$order.'][delete_empty_keys]', 'yes', 'checkbox');

			if ($this->get('delete_empty_keys') == '1') $input->setAttribute('checked', 'checked');

			$label->setValue(__('%s Automatically delete empty keys', array($input->generate())));

			$div->appendChild($label);
			$wrapper->appendChild($div);
		}

		/**
		 * Save field settings in section editor.
		 */
		public function commit() {
			if(!parent::commit()) return false;

			$id = $this->get('id');
			$handle = $this->handle();

			if($id === false) return false;

			$fields = array(
				'field_id' => $id,
				'validator' => $this->get('validator'),
				'default_keys' => $this->get('default_keys'),
				'delete_empty_keys' => $this->get('delete_empty_keys') == 'yes' ? '1' : '0'
			);

			return Symphony::Database()->insert($fields, "tbl_fields_{$handle}", true);
		}

	/*-------------------------------------------------------------------------
		Input:
	-------------------------------------------------------------------------*/

		public function displayPublishPanel(XMLElement &$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null, $entry_id = null) {
			extension_field_metakeys::appendAssets();

			// Label
			$label = Widget::Label($this->get('label'));
			if ($this->get('required') == 'no') {
				$label->appendChild(new XMLElement('i', __('Optional')));
			}
			$wrapper->appendChild($label);

			// Setup Duplicator
			$duplicator = new XMLElement('div', null, array('class' => 'frame metakeys-duplicator'));
			$pairs = new XMLElement('ol');
			$pairs->setAttribute('data-add', __('Add Pair'));
			$pairs->setAttribute('data-remove', __('Remove Pair'));

			// Add a blank template
			$pairs->appendChild(
				$this->buildPair()
			);

			// Loop through the default keys if this is a new entry.
			if(is_null($entry_id) && !is_null($this->get('default_keys'))) {
				// escape comma:
				$default_keys = str_replace('\\,', '[COMMA]', $this->get('default_keys'));
				$defaults = preg_split('/,\s*/', $default_keys, -1, PREG_SPLIT_NO_EMPTY);

				$field_handle = $this->get('element_name');

				if(is_array($defaults) && !empty($defaults)) foreach($defaults as $i => $key) {
					// Restore comma:
					$key = str_replace('[COMMA]', ',', $key);
					// Check if there is a value set:
					$a = explode('::', $key);
					if(count($a) == 2) {
						$_POST['fields'][$field_handle][$i]['value'] = $a[1];
						$key = $a[0];
					}
					$pairs->appendChild(
						$this->buildPair($key, $_POST['fields'][$field_handle][$i]['value'], $i)
					);
				}
			}

			// If there is actually $data, show that
			else if(!empty($data)) {

				// If there's only one 'pair', we'll need to make them an array
				// so the logic remains consistant
				if(!is_array($data['key_value'])) {
					$data = array(
						'key_value' => array($data['key_value']),
						'key_handle' => array($data['key_handle']),
						'value_value' => array($data['value_value']),
						'value_handle' => array($data['value_handle'])
					);
				}

				for($i = 0, $ii = count($data['key_value']); $i < $ii; $i++) {
					$pairs->appendChild(
						$this->buildPair($data['key_value'][$i], $data['value_value'][$i], $i)
					);
				}
			}

			$duplicator->appendChild($pairs);
			$wrapper->appendChild($duplicator);

			if (!is_null($flagWithError)) {
				$wrapper = Widget::Error($wrapper, $flagWithError);
			}
		}

		public function checkPostFieldData($data, &$message, $entry_id = null) {
			// Check required
			if($this->get('required') == 'yes' && (!isset($data[0]['key']) || General::strlen($data[0]['value']) == 0)) {
				$message = __(
					"'%s' is a required field.", array(
						$this->get('label')
					)
				);

				return self::__MISSING_FIELDS__;
			}

			// Return if it's allowed to be empty (and is empty)
			if(isset($data[0]['value']) && General::strlen($data[0]['value']) == 0) return self::__OK__;

			// Process Validation Rules
			if (!$this->applyValidationRules($data)) {
				$message = __(
					"'%s' contains invalid data. Please check the contents.", array(
						$this->get('label')
					)
				);

				return self::__INVALID_FIELDS__;
			}

			return self::__OK__;
		}

		public function processRawFieldData($data, &$status, &$message=null, $simulate=false, $entry_id=null) {
			$status = self::__OK__;

			$result = array();
			$delete_empty_keys = $this->get('delete_empty_keys') == 1;

			if(is_array($data)) foreach($data as $i => $pair) {

				// Key is not empty AND
				// Value is not empty OR we don't want to delete empty pairs
				// Then skip adding that pair in the result
				if(!empty($pair['key']) && (General::strlen($pair['value']) > 0 || $delete_empty_keys == false)) {
					$result['key_handle'][] = Lang::createHandle($pair['key']);
					$result['key_value'][] = $pair['key'];
					$result['value_handle'][] = Lang::createHandle($pair['value']);
					$result['value_value'][] = $pair['value'];
				}
			}

			// If there's no values, return null:
			if(empty($result)) return null;

			return $result;
		}

		public function getExampleFormMarkup(){
			$label = Widget::Label($this->get('label'));
			$label->appendChild(
				Widget::Input('fields['.$this->get('element_name').'][0][key]')
			);
			$label->appendChild(
				Widget::Input('fields['.$this->get('element_name').'][0][value]')
			);

			return $label;
		}

	/*-------------------------------------------------------------------------
		Import:
	-------------------------------------------------------------------------*/

		public function getImportModes() {
			return array(
				'getPostdata' =>	ImportableField::ARRAY_VALUE,
				'getString' =>		ImportableField::STRING_VALUE
			);
		}

		/**
		 * This function takes a string after XPath has resolved in the XMLImporter
		 * and it's job is to transform it into what the field expects as `$data`
		 * in the `processRawFieldData` function.
		 *
		 * @since 0.9.5
		 */
		public function prepareImportValue($data, $mode, $entry_id = null) {
			$message = $status = null;
			$modes = (object)$this->getImportModes();
			$temp = array();

			if($mode === $modes->getPostdata) {
				return $data;
			}
			else if($mode === $modes->getString) {
				$data = preg_split('/,\s*/', $data[0], -1, PREG_SPLIT_NO_EMPTY);
				$defaults = preg_split('/,\s*/', $this->get('default_keys'), -1, PREG_SPLIT_NO_EMPTY);
				$results = array();

				foreach($data as $key => $value) {

					// We have keys
					if(isset($defaults[$key])) {
						$temp['key'][$key] = $defaults[$key];
					}

					// Fake keys, while $key is zero based, a user doesn't
					// understand that, hence the + 1.
					else {
						$temp['key'][$key] = 'Key ' . $key + 1;
					}

					$temp['value'][$key] = $value;
				}

				return $temp;
			}

			return null;
		}

	/*-------------------------------------------------------------------------
		Export:
	-------------------------------------------------------------------------*/

		public function getExportModes() {
			return array(
				'getPostdata' =>	ExportableField::POSTDATA
			);
		}

		public function prepareExportValue($data, $mode, $entry_id = null) {
			$modes = (object)$this->getExportModes();

			if($mode === $modes->getPostdata) {
				return $data;
			}

			return null;
		}

	/*-------------------------------------------------------------------------
		Output:
	-------------------------------------------------------------------------*/

		public function fetchIncludableElements() {
			return array(
				$this->get('element_name'),
				$this->get('element_name') . ': named-keys'
			);
		}

		public function appendFormattedElement(XMLElement &$wrapper, $data, $encode = false, $mode = null, $entry_id = null) {
			if(!is_array($data) || empty($data)) return;

			$field = new XMLElement($this->get('element_name'));
			$field->setAttribute('mode',
				($mode == "named-keys") ? $mode : 'normal'
			);

			if(!is_array($data['key_handle'])) {
				$data = array(
					'key_handle' => array($data['key_handle']),
					'key_value' => array($data['key_value']),
					'value_handle' => array($data['value_handle']),
					'value_value' => array($data['value_value'])
				);
			}

			for($i = 0, $ii = count($data['key_handle']); $i < $ii; $i++) {

				$key = new XMLElement(
					($mode == "named-keys") ? $data['key_handle'][$i] : 'key'
				);

				$key->setAttribute('handle', $data['key_handle'][$i]);
				$key->setAttribute('name',
					General::sanitize($data['key_value'][$i])
				);

				$value = new XMLElement('value');
				$value->setAttribute('handle', $data['value_handle'][$i]);
				$value->setValue(
					General::sanitize($data['value_value'][$i])
				);

				$key->appendChild($value);
				$field->appendChild($key);
			}

			$wrapper->appendChild($field);
		}

		/**
		 * At this stage we will just return the Key's
		 */
		public function getParameterPoolValue(array $data, $entry_id=NULL) {
			return $data['key_handle'];
		}

		public function prepareTableValue($data, XMLElement $link = null, $entry_id = null) {
			if(is_null($data)) return __('None');

			$values = is_array($data['value_value'])
						? implode(', ', $data['value_value'])
						: $data['value_value'];

			return parent::prepareTableValue(array('value' => $values), $link);
		}

	/*-------------------------------------------------------------------------
		Filtering:
	-------------------------------------------------------------------------*/

		/**
		 * Accepted Filter options at this stage:
		 *
		 * colour			Key
		 * value: red		Value
		 * key-equals:		colour=red	Key Equals
		 */
		public function buildDSRetrievalSQL($data, &$joins, &$where, $andOperation = false) {
			if ($andOperation) {
				foreach($data as $filter) {
					$this->buildFilterQuery($filter, $joins, $where);
				}
			}
			else {
				$this->buildFilterQuery($data, $joins, $where);
			}

			return true;
		}

		private function buildFilterQuery($data, &$joins, &$where) {
			if (!is_array($data)) {
				$data = array($data);
			}

			$this->_key++;

			// Filter by values
			if (strpos($data[0], 'value:') === 0) {
				$data = $this->getCleanValues($data, 'value:');
				$this->buildFilterByValueQuery($data, $joins, $where);
			}

			// Filter by exact key/value pair
			elseif (strpos($data[0], 'key-equals:') === 0) {
				$data = $this->getCleanValues($data, 'key-equals:');
				$this->buildFilterByKeyEqualsQuery($data, $joins, $where);
			}

			// Filter by exact key/value pair
			elseif (strpos($data[0], 'key-contains:') === 0) {
				$data = $this->getCleanValues($data, 'key-contains:');
				$this->buildFilterByKeyContainsQuery($data, $joins, $where);
			}

			// Filter by value range
			elseif (strpos($data[0], 'key-ranges:') === 0) {
				$data = $this->getCleanValues($data, 'key-ranges:');
				$this->buildFilterByKeyRangesQuery($data, $joins, $where);
			}

			// Filter by key
			else {
				$data = $this->getCleanValues($data);
				$this->buildFilterByKeyQuery($data, $joins, $where);
			}
		}

		public function getCleanValues($data, $prefix = null) {
			for($i = 0; $i < count($data); $i++) {
				if ($prefix) {
					$data[$i] = trim(str_replace($prefix, '', $this->cleanValue($data[$i])));
				}
				else {
					$data[$i] = trim($this->cleanValue($data[$i]));
				}
			}

			return $data;
		}

		private function buildFilterByValueQuery($data, &$joins, &$where) {
			$field_id = $this->get('id');

			// Get values
			$value = implode("','", $data);

			// Build the joins
			$joins .= "
				LEFT JOIN
					`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
				ON
					(e.id = t{$field_id}_{$this->_key}.entry_id)
			";

			// Build the wheres
			$where .= "
				AND (
					t{$field_id}_{$this->_key}.value_value IN ('{$value}')
					OR
					t{$field_id}_{$this->_key}.value_handle IN ('{$value}')
				)
			";
		}

		private function buildFilterByKeyEqualsQuery($data, &$joins, &$where) {
			$field_id = $this->get('id');

			// Get all the keys/values
			$keys = array();
			$values = array();

			foreach($data as $filter) {
				list($key, $value) = explode('=', $filter);
				$keys[] = trim($key);
				$values[] = trim($value);
			}

			$key = implode("','", $keys);
			$value = implode("','", $values);

			// Build the joins
			$joins .= "
				LEFT JOIN
					`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
				ON
					(e.id = t{$field_id}_{$this->_key}.entry_id)
			";

			// Build the wheres
			$where .= "
				AND (
					t{$field_id}_{$this->_key}.key_value IN ('{$key}')
					OR
					t{$field_id}_{$this->_key}.key_handle IN ('{$key}')
				)
				AND (
					t{$field_id}_{$this->_key}.value_value IN ('{$value}')
					OR
					t{$field_id}_{$this->_key}.value_handle IN ('{$value}')
				)
			";
		}

		private function buildFilterByKeyContainsQuery($data, &$joins, &$where) {
			$field_id = $this->get('id');

			// Get all the keys/values
			$keys = array();
			$values = array();

			foreach($data as $filter) {
				list($key, $value) = explode('=', $filter);
				$keys[] = trim($key);
				$values[] = trim($value);
			}

			$key = implode("','", $keys);
			$value = implode("|", $values);

			// Build the joins
			$joins .= "
				LEFT JOIN
					`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
				ON
					(e.id = t{$field_id}_{$this->_key}.entry_id)
			";

			// Build the wheres
			$where .= "
				AND (
					t{$field_id}_{$this->_key}.key_value IN ('{$key}')
					OR
					t{$field_id}_{$this->_key}.key_handle IN ('{$key}')
				)
				AND (
					t{$field_id}_{$this->_key}.value_value REGEXP '{$value}'
					OR
					t{$field_id}_{$this->_key}.value_handle REGEXP '{$value}'
				)
			";
		}

		private function buildFilterByKeyRangesQuery($data, &$joins, &$where) {
			$field_id = $this->get('id');

			foreach($data as $filter) {
				preg_match("/^([a-z-]+)=((\d+|\.)(\.\.(\d+|\.))?)?/", $filter, $matches);
				if (!$matches[2]) {
					continue;
				}

				$this->_key++;

				$joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
					ON
						(e.id = t{$field_id}_{$this->_key}.entry_id)
				";
				$where .= "
					AND (
						t{$field_id}_{$this->_key}.key_value IN ('{$matches[1]}')
						OR
						t{$field_id}_{$this->_key}.key_handle IN ('{$matches[1]}')
					)
				";

				$from = $matches[3];
				$to = $matches[5];

				// Value
				if (!$to) {
					$where .= "
						AND (
							{$from} BETWEEN SUBSTRING_INDEX(value_value, '..', 1) AND SUBSTRING_INDEX(value_value, '..', -1)
						)
					";
				}

				// Less than
				elseif ($from === '.') {
					$where .= "
						AND (
							{$to} >= SUBSTRING_INDEX(value_value, '..', -1)
						)
					";
				}

				// More than
				elseif ($to === '.') {
					$where .= "
						AND (
							{$from} <= SUBSTRING_INDEX(value_value, '..', 1)
						)
					";
				}

				// Range
				else {
					$where .= "
						AND (
							{$from} BETWEEN SUBSTRING_INDEX(t{$field_id}_{$this->_key}.value_value, '..', 1) AND SUBSTRING_INDEX(t{$field_id}_{$this->_key}.value_value, '..', -1)
							OR
							{$to} BETWEEN SUBSTRING_INDEX(t{$field_id}_{$this->_key}.value_value, '..', 1) AND SUBSTRING_INDEX(t{$field_id}_{$this->_key}.value_value, '..', -1)
						)
					";
				}
			}
		}

		private function buildFilterByKeyQuery($data, &$joins, &$where) {
			$field_id = $this->get('id');

			// Get values
			$values = implode("', '", $data);

			// Build the joins
			$joins .= "
				LEFT JOIN
					`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
				ON
					(e.id = t{$field_id}_{$this->_key}.entry_id)
			";

			// Build the wheres
			$where .= "
				AND (
					t{$field_id}_{$this->_key}.key_value IN ('{$values}')
					OR
					t{$field_id}_{$this->_key}.key_handle IN ('{$values}')
				)
			";
		}

	}
