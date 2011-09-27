<?php

	Class extension_field_metakeys extends Extension{

		public function about(){
			return array(
				'name' => 'Field: Meta Keys',
				'version' => '0.9.4',
				'type' => 'Field, Interface',
				'release-date' => '2011-09-27',
				'author' => array(
					'name' => 'Brendan Abbott',
					'website' => 'http://www.bloodbone.ws',
					'email' => 'brendan@bloodbone.ws'
				)
			);
		}

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
					  	PRIMARY KEY  (`id`),
					  	UNIQUE KEY `field_id` (`field_id`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8;
				");
			}
			catch (Exception $ex) {
				$extension = $this->about();
				Administration::instance()->Page->pageAlert(__('An error occurred while installing %s. %s', array($extension['name'], $ex->getMessage())), Alert::ERROR);
				return false;
			}

			return true;
		}

		public function update($previousVersion){
            if(version_compare($previousVersion, '0.9.4', '<')) {
                Symphony::Database()->query('ALTER TABLE `tbl_fields_metakeys` ADD `delete_empty_keys` INT(1) NOT NULL DEFAULT \'1\';');
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
		Utitilites:
	-------------------------------------------------------------------------*/
		public static function appendAssets() {
			if(class_exists('Administration')
				&& Administration::instance() instanceof Administration
				&& Administration::instance()->Page instanceof HTMLPage
			) {
				Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/field_metakeys/assets/field_metakeys.publish.css', 'screen', 10000, false);
				Administration::instance()->Page->addScriptToHead(URL . '/extensions/field_metakeys/assets/field_metakeys.publish.js', 10001, false);
			}
		}
	}