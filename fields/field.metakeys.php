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
						CREATE TABLE IF NOT EXISTS `tbl_entries_data_%s` (
							`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
							`entry_id` INT(11) UNSIGNED NOT NULL,
							`key_handle` VARCHAR(255) NOT NULL,
							`key_value` TEXT NOT NULL,
							`value_handle` VARCHAR(255) DEFAULT NULL,
							`value_value` TEXT NOT NULL,
							PRIMARY KEY (`id`),
							KEY `entry_id` (`entry_id`)
						) TYPE=MyISAM DEFAULT CHARSET=utf8;
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

			return ($rule ? General::validateString($data, $rule) : true);
		}

		public function buildPair(XMLElement &$dl, $key = null, $value = null, $i = '') {
			$element_name = $this->get('element_name');

			$dt = new XMLElement('dt');
			$dt->appendChild(
				Widget::Input(
					"fields[$element_name][key][$i]", $key
				)
			);

			$dd = new XMLElement('dd');
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
				'default_keys' => $this->get('default_keys')
			);

			return Symphony::Database()->insert($fields, "tbl_fields_{$handle}", true);
		}

	/*-------------------------------------------------------------------------
		Input:
	-------------------------------------------------------------------------*/

		public function displayPublishPanel(&$wrapper, $data = null, $error = null, $prefix = null, $postfix = null, $entry_id = null) {
			extension_field_metakeys::appendAssets();

			$element_name = $this->get('element_name');
			$classes = array();
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
				for($i = 0, $ii = count($data['key_value']); $i < $ii; $i++) {
					$this->buildPair($dl, $data['key_value'][$i], $data['value_value'][$i], $i);
				}
			}

			#	Nothing, just prepend the template
			else {
				$this->buildPair($dl);
			}

			$label->appendChild($dl);

			if ($error != null) {
				$label = Widget::wrapFormElementWithError($label, $error);
			}

			$wrapper->appendChild($label);
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

			##	If there's no values, don't save the keys, just return
			if(empty($data['value'][0])) return null;

			$result = array();

			for($i = 0, $ii = count($data['key']); $i < $ii; $i++) {
				$result['key_handle'][$i] = Lang::createHandle($data['key'][$i]);
				$result['key_value'][$i] = $data['key'][$i];
				$result['value_handle'][$i] = Lang::createHandle($data['value'][$i]);
				$result['value_value'][$i] = $data['value'][$i];
			}

			return $result;
		}

	/*-------------------------------------------------------------------------
		Output:
	-------------------------------------------------------------------------*/

		public function appendFormattedElement(&$wrapper, $data, $encode = false, $mode = null) {

		}

	}
