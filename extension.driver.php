<?php

	Class extension_field_metakeys extends Extension{

	/*-------------------------------------------------------------------------
		Installation:
	-------------------------------------------------------------------------*/

		public function install(){
			try {
				Symphony::Database()->query("
					CREATE TABLE IF NOT EXISTS `tbl_fields_metakeys` (
						`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
						`field_id` INT(11) UNSIGNED NOT NULL,
						`validator` VARCHAR(255) DEFAULT NULL,
						`default_keys` TEXT DEFAULT NULL,
						`delete_empty_keys` INT (1) NOT NULL DEFAULT '1',
						PRIMARY KEY (`id`),
						UNIQUE KEY `field_id` (`field_id`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
				");
			}
			catch (Exception $ex) {
				$extension = $this->about();
				Administration::instance()->Page->pageAlert(__('An error occurred while installing %s. %s', array($extension['name'], $ex->getMessage())), Alert::ERROR);
				return false;
			}

			return true;
		}

		public function update($previousVersion = false){
			if(version_compare($previousVersion, '0.9.4', '<')) {
				Symphony::Database()->query('ALTER TABLE `tbl_fields_metakeys` ADD `delete_empty_keys` INT(1) NOT NULL DEFAULT \'1\';');
				// Get all the fields that are meta-keys:
				$ids = Symphony::Database()->fetchCol('id', 'SELECT `id` FROM `tbl_fields` WHERE `type` = \'metakeys\';');
				foreach($ids as $id) {
					Symphony::Database()->query('ALTER TABLE  `tbl_entries_data_'.$id.'`
						CHANGE `key_handle` `key_handle` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
						CHANGE `key_value` `key_value` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
						CHANGE `value_handle` `value_handle` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL ,
						CHANGE `value_value` `value_value` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL');
				}
			}
			return true;
		}

		public function uninstall(){
			if(parent::uninstall() == true){
				try {
					Symphony::Database()->query("DROP TABLE `tbl_fields_metakeys`");

					return true;
				}
				catch (Exception $ex) {
					$extension = $this->about();
					Administration::instance()->Page->pageAlert(__('An error occurred while uninstalling %s. %s', array($extension['name'], $ex->getMessage())), Alert::ERROR);
					return false;
				}
			}

			return false;
		}

	/*-------------------------------------------------------------------------
		Utilities:
	-------------------------------------------------------------------------*/
		public static function appendAssets() {
			if(class_exists('Administration')
				&& Administration::instance() instanceof Administration
				&& Administration::instance()->Page instanceof HTMLPage
			) {
				Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/field_metakeys/assets/field_metakeys.publish.css', 'screen', 100, false);
				Administration::instance()->Page->addScriptToHead(URL . '/extensions/field_metakeys/assets/field_metakeys.publish.js', 100, false);
			}
		}
	}
