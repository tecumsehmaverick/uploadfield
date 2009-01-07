<?php
	
	require_once(TOOLKIT . '/class.field.php');
	require_once(TOOLKIT . '/fields/field.upload.php');
	
	class Extension_AdvancedUploadField extends Extension {
	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/
		
		public function about() {
			return array(
				'name'			=> 'Field: Advanced Upload',
				'version'		=> '1.001',
				'release-date'	=> '2009-01-08',
				'author'		=> array(
					'name'			=> 'Rowan Lewis',
					'website'		=> 'http://pixelcarnage.com/',
					'email'			=> 'rowan@pixelcarnage.com'
				),
				'description'	=> 'An advanced upload field that supports dragging and dropping.'
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
					`filters` varchar(255) default NULL,
					PRIMARY KEY  (`id`),
					KEY `field_id` (`field_id`)
				)
			");
			
			return true;
		}
	}
	
?>