<?php
	
	class Extension_AdvancedUploadField extends Extension {
	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/
		
		public function about() {
			return array(
				'name'			=> 'Field: Advanced Upload',
				'version'		=> '1.0.6',
				'release-date'	=> '2009-09-09',
				'author'		=> array(
					'name'			=> 'Rowan Lewis',
					'website'		=> 'http://rowanlewis.com/',
					'email'			=> 'me@rowanlewis.com'
				),
				'description'	=> 'An enhanced upload field with image preview.'
			);
		}
		
		public function uninstall() {
			$this->_Parent->Database->query("DROP TABLE `tbl_fields_advancedupload`");
		}
		
		public function install() {
			$this->_Parent->Database->query("
				CREATE TABLE IF NOT EXISTS `tbl_fields_advancedupload` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`field_id` int(11) unsigned NOT NULL,
					`destination` varchar(255) NOT NULL,
					`validator` varchar(50) default NULL,
					`serialise` enum('yes','no') default NULL,
					PRIMARY KEY  (`id`),
					KEY `field_id` (`field_id`)
				)
			");
			
			return true;
		}
		
	/*-------------------------------------------------------------------------
		Utilites:
	-------------------------------------------------------------------------*/
		
		protected $addedHeaders = false;
		
		public function addHeaders($page) {
			if (!$this->addedHeaders) {
				$page->addStylesheetToHead(URL . '/extensions/advanceduploadfield/assets/publish.css', 'screen', 9745190);
				$page->addScriptToHead(URL . '/extensions/advanceduploadfield/assets/publish.js', 9745190);
				
				$this->addedHeaders = true;
			}
		}
	}
	
?>