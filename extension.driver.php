<?php

	Class extension_field_metakeys extends Extension{

		public function about(){
			return array(
				'name' => 'Field: Meta Keys',
				'version' => '0.9',
				'release-date' => '2010-10-11',
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

		public function uninstall(){
			if(parent::uninstall() == true){
				try {
					Symphony::Database()->query("DROP TABLE `tbl_fields_metakeys`");

					return true;
				}
				catch (Exception $ex) {
					$extension = $this->about();
					$this->pageAlert(__('An error occurred while uninstalling %s. %s', array($extension['name'], $ex->getMessage())), Alert::ERROR);
					return false;
				}
			}

			return false;
		}

		public function update($previousVersion){
			return true;
		}

		public function install(){
			try {
				Symphony::Database()->query("
					CREATE TABLE IF NOT EXISTS `tbl_fields_metakeys` (
						`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
						`field_id` INT(11) UNSIGNED NOT NULL,
						`validator` VARCHAR(255) DEFAULT NULL,
						`default_keys` TEXT DEFAULT NULL,
					  	PRIMARY KEY  (`id`),
					  	UNIQUE KEY `field_id` (`field_id`)
					) TYPE=MyISAM DEFAULT CHARSET=utf8;
				");
			}
			catch (Exception $ex) {
				$extension = $this->about();
				Administration::instance()->Page->pageAlert(__('An error occurred while installing %s. %s', array($extension['name'], $ex->getMessage())), Alert::ERROR);
				return false;
			}

			return true;
		}

	/*-------------------------------------------------------------------------
		Utitilites:
	-------------------------------------------------------------------------*/
		public static function appendAssets() {
			Administration::instance()->Page->addStylesheetToHead(URL . '/extensions/field_metakeys/assets/default.css', 'screen', 10000, false);
			Administration::instance()->Page->addScriptToHead(URL . '/extensions/field_metakeys/assets/default.js', 10001, false);
		}
	}