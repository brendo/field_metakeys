<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	Class fieldMetaKeys extends Field {

		public function __construct(&$parent) {
			parent::__construct($parent);
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
							`key_handle` VARCHAR(255) NOT NULL,
							`key_value` TEXT NOT NULL,
							`value_handle` VARCHAR(255) DEFAULT NULL,
							`value_value` TEXT NOT NULL,
							PRIMARY KEY (`id`),
							KEY `entry_id` (`entry_id`)
						) ENGINE=MyISAM DEFAULT CHARSET=utf8;
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

		public function buildPair(XMLElement &$dl, $key = null, $value = null, $i = '') {
			$element_name = $this->get('element_name');

			$dt = new XMLElement('dt', __('Key'));
			$dt->appendChild(
				Widget::Input(
					"fields[$element_name][key][$i]", $key
				)
			);

			$dd = new XMLElement('dd', __('Value'));
			$dd->appendChild(
				Widget::Input(
					"fields[$element_name][value][$i]", $value
				)
			);

			$dl->appendChild($dt);
			$dl->appendChild($dd);
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
		public function displaySettingsPanel(&$wrapper, $errors = null) {
			##	Initialize field settings based on class defaults (name, placement)
			parent::displaySettingsPanel($wrapper, $errors);

			$order = $this->get('sortorder');

			$group = new XMLElement('div');
			$group->setAttribute('class', 'group');

			##	Default Keys
			$label = Widget::Label(__('Default Keys'));
			$label->appendChild(
				new XMLElement('i', __('Optional'))
			);
			$label->appendChild(Widget::Input(
				"fields[{$order}][default_keys]", $this->get('default_keys')
			));

			$group->appendChild($label);

			##	Validator
			$div = new XMLElement('div');
			$this->buildValidationSelect(
				$div, $this->get('validator'), "fields[{$order}][validator]"
			);

			$group->appendChild($div);

			$wrapper->appendChild($group);

            ##  Automatic delete
            $label = Widget::Label();
            $input = Widget::Input('fields['.$order.'][delete_empty_keys]', 'yes', 'checkbox');

            if ($this->get('delete_empty_keys') == '1') $input->setAttribute('checked', 'checked');

            $label->setValue(__('%s Automaticly delete empty keys', array($input->generate())));

            $wrapper->appendChild($label);

			##	Defaults
			$this->appendRequiredCheckbox($wrapper);
			$this->appendShowColumnCheckbox($wrapper);
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

		public function displayPublishPanel(XMLElement &$wrapper, $data = null, $error = null, $prefix = null, $postfix = null, $entry_id = null) {
			extension_field_metakeys::appendAssets();

			$dl = new XMLElement('dl');

			#	Label
			$label = Widget::Label($this->get('label'));
			if ($this->get('required') == 'no') {
				$label->appendChild(new XMLElement('i', __('Optional')));
			}

			#	Loop through the default keys if this is a new entry.
			if(is_null($entry_id) && !is_null($this->get('default_keys'))) {
				$defaults = preg_split('/,\s*/', $this->get('default_keys'), -1, PREG_SPLIT_NO_EMPTY);

				if(is_array($defaults) && !empty($defaults)) foreach($defaults as $key) {
					$this->buildPair($dl, $key);
				}
			}

			#	If there is actually $data, show that
			else if(!is_null($data)) {

				#	If there's only one 'pair', we'll need to make them an array
				#	so the logic remains consistant
				if(!is_array($data['key_value'])) {
					$data = array(
						'key_value' => array($data['key_value']),
						'key_handle' => array($data['key_handle']),
						'value_value' => array($data['value_value']),
						'value_handle' => array($data['value_handle'])
					);
				}

				for($i = 0, $ii = count($data['key_value']); $i < $ii; $i++) {
					$this->buildPair($dl, $data['key_value'][$i], $data['value_value'][$i], $i);
				}
			}

			#	Nothing, just prepend the template
			else {
				$this->buildPair($dl);
			}

			$wrapper->appendChild($label);
			$wrapper->appendChild($dl);

			if ($error != null) {
				$wrapper = Widget::wrapFormElementWithError($wrapper, $error);
			}
		}

		public function checkPostFieldData($data, &$message = null, $entry_id = null) {
			##	Check required
			if($this->get('required') == 'yes' && (!isset($data['key']) || empty($data['value'][0]))) {
				$message = __(
					"'%s' is a required field.", array(
						$this->get('label')
					)
				);

				return self::__MISSING_FIELDS__;
			}

			##	Return if it's allowed to be empty (and is empty)
			if(empty($data['value'][0])) return self::__OK__;

			##	Process Validation Rules
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

		public function processRawFieldData($data, &$status, $simulate = false, $entry_id = null) {
			$status = self::__OK__;

			$result = array();
            $delete_empty_keys = $this->get('delete_empty_keys') == 1;

			for($i = 0, $ii = count($data['key']); $i < $ii; $i++) {
    			##	If there's no values, don't save the keys:
                if(!empty($data['value'][$i]) || $delete_empty_keys == false)
                {
                    $result['key_handle'][$i] = Lang::createHandle($data['key'][$i]);
                    $result['key_value'][$i] = $data['key'][$i];
                    $result['value_handle'][$i] = Lang::createHandle($data['value'][$i]);
                    $result['value_value'][$i] = $data['value'][$i];
                }
			}

            ##	If there's no values, return null:
            if(empty($result)) return null;

			return $result;
		}

		public function getExampleFormMarkup(){
			$label = Widget::Label($this->get('label'));
			$label->appendChild(
				Widget::Input('fields['.$this->get('element_name').'][key][]')
			);
			$label->appendChild(
				Widget::Input('fields['.$this->get('element_name').'][value][]')
			);

			return $label;
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

		/*
		**	At this stage we will just return the Key's
		*/
		public function getParameterPoolValue($data) {
			return is_array($data['key_handle'])
						? implode(', ', $data['key_handle'])
						: $data['key_handle'];
		}

		public function prepareTableValue($data, XMLElement $link = null) {
			if(is_null($data)) return __('None');

			$values = is_array($data['value_value'])
						? implode(', ', $data['value_value'])
						: $data['value_value'];

			return parent::prepareTableValue(array('value' => $values), $link);
		}

	/*-------------------------------------------------------------------------
		Filtering:
	-------------------------------------------------------------------------*/

		/*
		**	Accepted Filter options at this stage:
		**
		**	colour			Key
		**	value: red		Value
		**	key-equals: 	colour=red	Key Equals
		*/
		public function buildDSRetrivalSQL($data, &$joins, &$where, $andOperation = false) {

			$field_id = $this->get('id');

			#	Value filter
			if (preg_match('/^value:.*/', $data[0])) {
				$this->_key++;

				#	Split all of the possible combos
				$data[0] = trim(str_replace('value:', '', $this->cleanValue($data[0])));

				$joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
					ON
						(e.id = t{$field_id}_{$this->_key}.entry_id)
				";

				$value = implode("','", $data);

				#	Build the wheres
				$where .= "
					AND (
						t{$field_id}_{$this->_key}.value_value IN ('{$value}')
						OR
						t{$field_id}_{$this->_key}.value_handle IN ('{$value}')
					)
				";
			}

			#	Key equals filter
			else if (preg_match('/^key-equals:.*/', $data[0])) {
				$this->_key++;

				#	Split all of the possible combos
				$data[0] = trim(str_replace('key-equals:', '', $this->cleanValue($data[0])));

				$joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
					ON
						(e.id = t{$field_id}_{$this->_key}.entry_id)
				";

				#	Get all the keys/values
				$keys = array();
				$values = array();

				foreach($data as $filter) {
					$keys = array_merge($keys, preg_split('/=\w+[,+-\s]*\w+/', $filter, null, 1));
					$values = array_merge($values, preg_split('/\w+[,+-\s]*\w+=/', $filter, null, 1));
				}

				$key = implode("','", $keys);
				$value = implode("','", $values);

				#	Build the wheres
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

			elseif ($andOperation) {
				foreach ($data as $value) {
					$this->_key++;
					$value = $this->cleanValue($value);
					$joins .= "
						LEFT JOIN
							`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
							ON (e.id = t{$field_id}_{$this->_key}.entry_id)
					";
					$where .= "
						AND	(
							t{$field_id}_{$this->_key}.key_value = '{$value}'
							OR
							t{$field_id}_{$this->_key}.key_handle = '{$value}'
						)
					";
				}

			}

			#	Default Key match
			else {
				if (!is_array($data)) $data = array($data);

				foreach ($data as &$value) {
					$value = $this->cleanValue($value);
				}

				$this->_key++;
				$data = implode("', '", $data);
				$joins .= "
					LEFT JOIN
						`tbl_entries_data_{$field_id}` AS t{$field_id}_{$this->_key}
					ON
						(e.id = t{$field_id}_{$this->_key}.entry_id)
				";
				$where .= "
					AND	(
						t{$field_id}_{$this->_key}.key_value IN ('{$data}')
						OR
						t{$field_id}_{$this->_key}.key_handle IN ('{$data}')
					)
				";
			}

			return true;
		}

	}