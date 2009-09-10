<?php
	
	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	class FieldAdvancedUpload extends Field {
		protected $_mimes = array();
		protected $_driver = null;
		
	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/
		
		public function __construct(&$parent) {
			parent::__construct($parent);
			
			$this->_name = 'Advanced Upload';
			$this->_required = true;
			$this->_driver = $this->_engine->ExtensionManager->create('advanceduploadfield');
			$this->_mimes = array(
				'image'	=> array(
					'image/bmp',
					'image/gif',
					'image/jpg',
					'image/jpeg',
					'image/png'
				),
				'text'	=> array(
					'text/plain',
					'text/html'
				)
			);
			
			$this->set('show_column', 'yes');
			$this->set('required', 'yes');
		}
		
		public function createTable() {
			$field_id = $this->get('id');
			
			return $this->_engine->Database->query("
				CREATE TABLE IF NOT EXISTS `tbl_entries_data_{$field_id}` (
					`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
					`entry_id` INT(11) UNSIGNED NOT NULL,
					`name` TEXT DEFAULT NULL,
					`file` TEXT DEFAULT NULL,
					`size` INT(11) UNSIGNED NOT NULL,
					`mimetype` VARCHAR(50) NOT NULL,
					`meta` VARCHAR(255) DEFAULT NULL,
					PRIMARY KEY (`id`),
					KEY `entry_id` (`entry_id`),
					KEY `mimetype` (`mimetype`),
					FULLTEXT KEY `name` (`name`),
					FULLTEXT KEY `file` (`file`)
				)
			");
		}
		
		public function canFilter() {
			return true;
		}
		
		public function canImport() {
			return true;
		}
		
		public function isSortable() {
			return true;
		}	
		
		public function getExampleFormMarkup() {
			$handle = $this->get('element_name');
			
			$label = Widget::Label($this->get('label'));
			$label->appendChild(Widget::Input('fields[{$handle}]', null, 'file'));
			
			return $label;
		}
		
		public function entryDataCleanup($entry_id, $data) {
			$file_location = WORKSPACE . '/' . ltrim($data['file'], '/');
			
			if (is_file($file_location)) General::deleteFile($file_location);
			
			parent::entryDataCleanup($entry_id);
			
			return true;
		}
		
		public function sanitizeDataArray(&$data) {
			if (!isset($data['file']) or $data['file'] == '') return false;
			
			if (!isset($data['name']) or $data['name'] == '') {
				$data['name'] = basename($data['file']);
			}
			
			if (!isset($data['size']) or empty($data['size'])) {
				$data['size'] = 0;
			}
			
			if (!isset($data['mimetype']) or $data['mimetype'] == '') {
				$data['mimetype'] = 'application/octet-stream';
			}
			
			return true;
		}
		
	/*-------------------------------------------------------------------------
		Settings:
	-------------------------------------------------------------------------*/
		
		public function checkFields(&$errors, $checkForDuplicates = true) {
			if (!is_writable(DOCROOT . $this->get('destination') . '/')) {
				$errors['destination'] = 'Folder is not writable. Please check permissions.';
			}
			
			parent::checkFields($errors, $checkForDuplicates);
		}
		
		public function displaySettingsPanel(&$wrapper, $errors = null) {
			parent::displaySettingsPanel($wrapper, $errors);
			
			$order = $this->get('sortorder');
			
		// Destination --------------------------------------------------------
			
			$ignore = array(
				'events',
				'data-sources',
				'text-formatters',
				'pages',
				'utilities'
			);
			$directories = General::listDirStructure(WORKSPACE, true, 'asc', DOCROOT, $ignore);		   
			
			$label = Widget::Label('Destination Directory');
			
			$options = array(
				array('/workspace', false, '/workspace')
			);
			
			if (!empty($directories) and is_array($directories)) {
				foreach ($directories as $d) {
					$d = '/' . trim($d, '/');
					
					if (!in_array($d, $ignore)) {
						$options[] = array($d, ($this->get('destination') == $d), $d);
					}
				}	
			}
			
			$label->appendChild(Widget::Select(
				"fields[{$order}][destination]", $options
			));
				
			if (isset($errors['destination'])) {
				$label = Widget::wrapFormElementWithError($label, $errors['destination']);
			}
			
			$wrapper->appendChild($label);
			
		// Validator ----------------------------------------------------------
			
			$this->buildValidationSelect($wrapper, $this->get('validator'), "fields[{$order}][validator]", 'upload');
			
		// Serialise ----------------------------------------------------------
			
			$label = Widget::Label();
			$input = Widget::Input(
				"fields[{$order}][serialise]", 'yes', 'checkbox'
			);
			
			if ($this->get('serialise') == 'yes') $input->setAttribute('checked', 'checked');
			
			$label->setValue($input->generate() . ' ' . __('Serialise file names'));
			$wrapper->appendChild($label);
			
			$this->appendRequiredCheckbox($wrapper);
			$this->appendShowColumnCheckbox($wrapper);
		}
		
		public function commit() {
			if (!parent::commit() or $field_id === false) return false;
			
			$field_id = $this->get('id');
			$handle = $this->handle();
			
			$fields = array(
				'field_id'		=> $field_id,
				'destination'	=> $this->get('destination'),
				'validator'		=> $this->get('validator'),
				'serialise'		=> ($this->get('serialise') == 'yes' ? 'yes' : 'no')
			);
			
			$this->_engine->Database->query("
				DELETE FROM
					`tbl_fields_{$handle}`
				WHERE
					`field_id` = '{$field_id}'
				LIMIT 1
			");
			
			return $this->_engine->Database->insert($fields, "tbl_fields_{$handle}");
		}
		
	/*-------------------------------------------------------------------------
		Publish:
	-------------------------------------------------------------------------*/
		
		public function displayPublishPanel(&$wrapper, $data = null, $error = null, $prefix = null, $postfix = null, $entry_id = null) {
			$this->_driver->addHeaders($this->_engine->Page);
			
			if (!$error and !is_writable(DOCROOT . $this->get('destination') . '/')) {
				$error = 'Destination folder, <code>'.$this->get('destination').'</code>, is not writable. Please check permissions.';
			}
			
			$handle = $this->get('element_name');
			
		// Image --------------------------------------------------------------
			
			$label = Widget::Label($this->get('label'));
			
			if ($this->get('required') != 'yes') {
				$label->appendChild(new XMLElement('i', 'Optional'));
			}
			
			$wrapper->appendChild($label);
			
			if ($error == null and !empty($data['file'])) {
				if (!is_file(WORKSPACE . $data['file'])) {
					$error = __('Destination file could not be found.');
				}
				
				else if (in_array($data['mimetype'], $this->_mimes['image'])) {
					$preview = new XMLElement('div');
					$preview->setAttribute('class', 'preview');
					$image = new XMLElement('img');
					$image->setAttribute('src', URL . '/workspace' . $data['file']);
					$preview->appendChild($image);
					$wrapper->appendChild($preview);
				}
				
				$details = new XMLElement('dl');
				$details->setAttribute('class', 'details');
				
				$link = new XMLElement('a', __('Clear File'));
				$item = new XMLElement('dt', $link->generate());
				$item->setAttribute('class', 'clear');
				$details->appendChild($item);
				
				$link = Widget::Anchor($data['name'], URL . '/workspace' . $data['file']);
				$item = new XMLElement('dt', $link->generate());
				$item->setAttribute('class', 'popup');
				$details->appendChild($item);
				
				$details->appendChild(new XMLElement('dt', __('Size:')));
				$details->appendChild(new XMLElement('dd', General::formatFilesize($data['size'])));
				$details->appendChild(new XMLElement('dt', __('Type:')));
				$details->appendChild(new XMLElement('dd', General::sanitize($data['mimetype'])));
				$wrapper->appendChild($details);
			}
			
			$upload = new XMLElement('span');
			$upload->setAttribute('class', 'upload');
			$upload->appendChild(Widget::Input(
				"fields{$prefix}[{$handle}]{$postfix}",
				$data['file'], ($data['file'] ? 'hidden' : 'file')
			));
			$wrapper->appendChild($upload);
			
			if ($error != null) {
				$wrapper = Widget::wrapFormElementWithError($wrapper, $error);
			}
		}
		
	/*-------------------------------------------------------------------------
		Input:
	-------------------------------------------------------------------------*/
		
		protected function getHashedFilename($filename) {
			preg_match('/(.*?)(\.[^\.]+)$/', $filename, $meta);
			
			$filename = sprintf(
				'%s-%s%s',
				Lang::createHandle($meta[1]),
				md5(time()), $meta[2]
			);
			
			return $filename;
		}
		
		public function checkPostFieldData($data, &$message, $entry_id = null) {
			$label = $this->get('label');
			$message = null;
			
			if (empty($data) or $data['error'] == UPLOAD_ERR_NO_FILE) {
				if ($this->get('required') == 'yes') {
					$message = "'{$label}' is a required field.";
					
					return self::__MISSING_FIELDS__;		
				}
				
				return self::__OK__;
			}
			
			// Its not an array, so just retain the current data and return
			if (!is_array($data)) return self::__OK__;
			
			if (!is_writable(DOCROOT . $this->get('destination') . '/')) {
				$message = 'Destination folder, <code>' . $this->get('destination') . '</code>, is not writable. Please check permissions.';
				
				return self::__ERROR__;
			}

			if ($data['error'] != UPLOAD_ERR_NO_FILE and $data['error'] != UPLOAD_ERR_OK) {
				switch($data['error']) {
					case UPLOAD_ERR_INI_SIZE:
						$size = (
							is_numeric(ini_get('upload_max_filesize'))
							? General::formatFilesize(ini_get('upload_max_filesize'))
							: ini_get('upload_max_filesize')
						);
						$message = __('File chosen in \'%s\' exceeds the maximum allowed upload size of %s specified by your host.', $label, $size);
						break;
						
					case UPLOAD_ERR_FORM_SIZE:
						$size = General::formatFilesize(Symphony::Configuration()->get('max_upload_size', 'admin'));
						$message = __('File chosen in \'%s\' exceeds the maximum allowed upload size of {$size}, specified by Symphony.', $label);
						break;
						
					case UPLOAD_ERR_PARTIAL:
					case UPLOAD_ERR_NO_TMP_DIR:
						$message = __('File chosen in \'%s\' was only partially uploaded due to an error.', $label);
						break;
						
					case UPLOAD_ERR_CANT_WRITE:
						$message = __('Uploading \'%s\' failed. Could not write temporary file to disk.', $label);
						break;
						
					case UPLOAD_ERR_EXTENSION:
						$message = __('Uploading \'%s\' failed. File upload stopped by extension.', $label);
						break;
				}
				
				return self::__ERROR_CUSTOM__;
			}
			
			// Sanitize the filename:
			if ($this->get('serialise') == 'yes' and is_array($data) and isset($data['name'])) {
				$data['name'] = $this->getHashedFilename($data['name']);
			}
			
			if ($this->get('validator') != null) {
				$rule = $this->get('validator');
				
				if (!General::validateString($data['name'], $rule)) {
					$message = "File chosen in '{$label}' does not match allowable file types for that field.";
					
					return self::__INVALID_FIELDS__;
				}
			}
			
			$abs_path = DOCROOT . '/' . trim($this->get('destination'), '/');
			$new_file = $abs_path . '/' . $data['name'];
			$existing_file = null;
			
			if ($entry_id) {
				$field_id = $this->get('id');
				$row = $this->Database->fetchRow(0, "
					SELECT
						f.*
					FROM
						`tbl_entries_data_{$field_id}` AS f
					WHERE
						f.entry_id = '{$entry_id}'
					LIMIT 1
				");
				$existing_file = $abs_path . '/' . trim($row['file'], '/');
			}
			
			if (($existing_file != $new_file) and file_exists($new_file)) {
				$message = __('A file with the name %s already exists in %s. Please rename the file first, or choose another.', $data['name'], $this->get('destination'));
				
				return self::__INVALID_FIELDS__;				
			}
			
			return self::__OK__;
		}
		
		public function processRawFieldData($data, &$status, $simulate = false, $entry_id = null) {
			$status = self::__OK__;
			
			// Recal existing data:
			$existing = Symphony::Database()->fetchRow(
				0, sprintf(
					"
						SELECT
							f.name,
							f.file,
							f.size,
							f.mimetype,
							f.meta
						FROM
							`tbl_entries_data_%s` AS f
						WHERE
							f.entry_id = '%s'
						LIMIT 1
					",
					$this->get('id'), $entry_id
				)
			);
			
			if ($simulate) return;
			
			// No file sent, cleanup existing:
			if (is_null($data) or $data == '' or (isset($data['error']) and $data['error'] != UPLOAD_ERR_OK)) {
				if (isset($existing['file']) and is_file(WORKSPACE . $existing['file'])) {
					General::deleteFile(WORKSPACE . $existing['file']);
				}
				
				return;
			}
			
			// Accept a path:
			if (is_string($data)) {
				// Existing data found:
				if (is_array($existing) and $existing['file'] == $data) {
					return $existing;
				}
				
				// Examine file:
				else if (is_file(WORKSPACE . '/' . $data)) {
					return array(
						'name'		=> basename($data),
						'file'		=> $data,
						'mimetype'	=> $this->getMimeType($data),
						'size'		=> filesize(WORKSPACE . '/' . $data),
						'meta'		=> serialize($this->getMetaInfo(WORKSPACE . $data, $this->getMimeType($data)))
					);
				}
			}
			
			$path = rtrim(preg_replace('%^/workspace%', '', $this->get('destination')), '/');
			$name = $data['name'];
			
			// Sanitize the filename:
			if ($this->get('serialise') == 'yes') {
				$data['name'] = $this->getHashedFilename($data['name']);
			}
			
			if (!General::uploadFile(
				DOCROOT . '/' . trim($this->get('destination'), '/'),
				$data['name'], $data['tmp_name'],
				Symphony::Configuration()->get('write_mode', 'file')
			)) {
				$message = __(
					'There was an error while trying to upload the file <code>%s</code> to the target directory <code>workspace/%s</code>.',
					$data['name'], $path
				);
				$status = self::__ERROR_CUSTOM__;
				return;
			}
			
			// Remove file being replaced:
			if (isset($existing['file']) and is_file(WORKSPACE . $existing['file'])) {
				General::deleteFile(WORKSPACE . $existing['file']);
			}
			
			return array(
				'name'		=> $name,
				'file'		=> $path . '/' . trim($data['name'], '/'),
				'size'		=> $data['size'],
				'mimetype'	=> $data['type'],
				'meta'		=> serialize($this->getMetaInfo(WORKSPACE . $file, $data['type']))
			);
		}
		
		protected function getMimeType($file) {
			if (in_array('image/' . General::getExtension($file), $this->_mimes['image'])) {
				return 'image/' . General::getExtension($file);
			}
			
			return 'application/octet-stream';
		}
		
		protected function getMetaInfo($file, $type) {
			$meta = array(
				'creation'	=> DateTimeObj::get('c', filemtime($file))
			);
			
			if (in_array($type, $this->_mimes['image'])) {
				if (!$data = @getimagesize($file)) return $meta;
				
				$meta['width']	= $data[0];
				$meta['height']   = $data[1];
				$meta['type']	 = $data[2];
				$meta['channels'] = $data['channels'];
			}
			
			return $meta;
		}
		
	/*-------------------------------------------------------------------------
		Output:
	-------------------------------------------------------------------------*/
		
		public function appendFormattedElement(&$wrapper, $data) {
			if (!$this->sanitizeDataArray($data)) return null;
			
			$item = new XMLElement($this->get('element_name'));
			$item->setAttributeArray(array(
				'size'	=> General::formatFilesize($data['size']),
				'type'	=> General::sanitize($data['mimetype']),
				'name'	=> General::sanitize($data['name'])
			));
			
			$item->appendChild(new XMLElement('path', str_replace(WORKSPACE, NULL, dirname(WORKSPACE . $data['file']))));
			$item->appendChild(new XMLElement('file', General::sanitize(basename($data['file']))));
			
			$meta = unserialize($data['meta']);
			
			if (is_array($meta) and !empty($meta)) {
				$item->appendChild(new XMLElement('meta', null, $meta));
			}
			
			$wrapper->appendChild($item);
		}
		
		public function prepareTableValue($data, XMLElement $link = null) {
			if (!$this->sanitizeDataArray($data)) return null;
			
			if ($link) {
				$link->setValue($data['name']);
				
				return $link->generate();
				
			} else {
				$link = Widget::Anchor($data['name'], URL . '/workspace' . $data['file']);
				
				return $link->generate();
			}
		}
	}
	
?>