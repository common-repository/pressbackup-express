<?php

class PressExpressMisc {

	function zip($type='shell', $args = array()) {
		global $pressexpress;

		if ($type == 'shell' && $bin = $this->checkShell('zip')) {

			$compression = (isset($args['compression'])) ? '-' . $args['compression'] : '';
			$cmd = 'cd ' . $args['context_dir'] . ';';
			$cmd .= $bin . ' -r -q ' . $compression . ' ' . $args['zip'] . ' ' . $args['dir'] . ';';
			$cmd .= 'chmod 0777 ' . $args['zip'] . ';';
			$cmd .= $bin . ' -T ' . $args['zip'] . ';';
			$res = $this->ShellRun($cmd);
			return ($res && strpos(strtolower($res[0]), 'ok') !== false);
		} 

		if ($type == 'php') {

			//create zip file
			$zip = new ZipArchive();
			if ($zip->open($args['zip'], ZIPARCHIVE::OVERWRITE) !== true) {
				$pressexpress->Session->write( 'error_msg',  __("Can not create: ",'pressbackup') . $args['zip']);
				return false;
			}

			//add files to the zip
			//error messages will be set in zipFolder function
			@chdir($args['context_dir']);
			if (!$this->zipFolder($args['dir'], $zip)) {
				$zip->close();
				return false;
			}
			$zip->close();

			//check if it was created and filled without problems
			if ($zip->open($args['zip'], ZIPARCHIVE::CHECKCONS) !== TRUE) {
				$pressexpress->Session->write( 'error_msg',  __("Zip file corrupt: ",'pressbackup') . $args['zip']);
				$zip->close();
				return false;
			}
			$zip->close();
			
			return true;
		}

		return false;
	}

	function php($args=array()) {
		if (isset($args['file']) && !is_file($args['file'])) {
			return false;
		}
		if (isset($args['split']) && !preg_match("/^[A-Z\|]*$/", $args['split'])) {
			return false;
		}
		if (isset($args['args']) && !preg_match("/^[a-zA-Z0-9\=]*$/", $args['args'])) {
			return false;
		}

		$bin = $this->checkShell('php');
		$cmd = $bin . ' ' . $args['file'] . ' "' . $args['split'] . '" "' . $args['args'] . '" > /dev/null  2>&1 &';
		return $this->ShellRun($cmd);
	}

	function checkShell($type = 'zip') {

		if ($type == 'zip') {
			if (!$res = $this->ShellRun('whereis -b zip')) {
				return false;
			}

			$res = str_replace('zip: ', '', $res[0]);
			$binaries = explode(' ', $res);
			for ($i = 0; $i < count($binaries); $i++) {
				$res = $this->ShellRun($binaries[$i] . ' -T popo');
				if ($res && strpos(strtolower($res[1]), 'zip') !== false) {
					return $binaries[$i];
				}
			}
		} elseif ($type == 'php') {

			if (!$res = $this->ShellRun('whereis -b php')) {
				return false;
			}

			$res = str_replace('php: ', '', $res[0]);
			$binaries = explode(' ', $res);
			for ($i = 0; $i < count($binaries); $i++) {
				$res = $this->ShellRun($binaries[$i] . ' -r "echo \'hola\';"');
				if (str_replace(array('\n', ''), '', $res[0]) == 'hola') {
					return $binaries[$i];
				}
			}
			return false;
		}
	}

	private function ShellRun($cmd) {
		$output = array();
		$return_var = 1;
		@exec($cmd, $output, $return_var);
		return $output;
	}

	function zipFolder($dir, &$zipArchive) {
		global $pressexpress;

		if (!is_dir($dir) || !$dh = opendir($dir)) {
			$pressexpress->Session->write( 'error_msg',  __("Can not open dir: ",'pressbackup') . $dir);
			return false;
		}

		// Loop through all the files
		while (($file = readdir($dh)) !== false) {

			//exclude wrong files
			if (($file == '.') || ($file == '..') || ( is_file( $dir . $file ) && !@is_readable($dir . $file) ) ) {
				continue;
			}

			//If it's a folder, run the function again!
			if (is_dir($dir . $file) && !$this->zipFolder($dir . $file . DS, $zipArchive)) {
					closedir($dh);
					return false;
			}

			//else add it to de zip
			if (is_file($dir . $file) && !$zipArchive->addFile($dir . $file)) {
				$pressexpress->Session->write( 'error_msg',  __("Can not add file: ",'pressbackup') . $dir . $file);
				closedir($dh);
				return false;
			}
		}
		closedir($dh);
		return true;
	}

	function perpare_folder($dir) {
		$this->actionFolder($dir . DS, array('function' => 'del'));
		@mkdir($dir);
		$this->actionFolder($dir . DS, array('function' => 'chmod', 'param' => array(0777)));
	}

	function actionFolder($dir, $option) {
		if (is_file($dir)) {
			if ($option['function'] == 'del') {
				return @unlink($dir);
			} elseif ($option['function'] == 'chmod') {
				return @chmod($dir, $option['param'][0]);
			}
		} elseif (is_dir($dir)) {
			$scan = scandir($dir);
			foreach ($scan as $index => $path) {
				if (!in_array($path, array('.', '..'))) {
					$this->actionFolder($dir . DS . $path, $option);
				}
			}
			if ($option['function'] == 'del') {
				return @rmdir($dir);
			} elseif ($option['function'] == 'chmod') {
				return @chmod($dir, $option['param'][0]);
			}
		}
	}

	function msort($type='S3', $array, $id="time") {// type = S3 , Pro , DropBox

		//Normalize the array with de backup list
		$normalized_array = array();
		if ($type == 'S3') {
			foreach ($array as $item) {
				$normalized_array[] = $item;
			}
		} elseif ($type == 'Pro') {
			foreach ($array['items'] as $item) {
				$normalized_array[] = $item['item'];
			}
		} elseif ($type == 'dBox') {
			foreach ($array->contents as $file) {
				$normalized_array[] = array('name' => trim($file->path, '/'), 'time' => strtotime($file->modified), 'size' => $file->bytes, 'hash' => "");
			}
		}
		elseif ($type == 'Local') {
			$normalized_array = $array;
		}

		//Sort by $id
		$temp_array = array();
		while (count($normalized_array) > 0) {
			$lowest_id = 0;
			$index = 0;
			foreach ($normalized_array as $item) {
				if (isset($item[$id]) && $normalized_array[$lowest_id][$id]) {
					if (strcmp($item[$id], $normalized_array[$lowest_id][$id]) > 0) {
						$lowest_id = $index;
					}
				}
				$index++;
			}
			$temp_array[] = $normalized_array[$lowest_id];
			$normalized_array = array_merge(array_slice($normalized_array, 0, $lowest_id), array_slice($normalized_array, $lowest_id + 1));
		}
		return $temp_array;
	}

	function filter_files($type='all_sites', $backup_files) {
		if ($type == 'all_sites') {
			return $backup_files;
		}
		$blog_name = str_replace(' ', '-', $this->normalize_string(strtolower(trim(get_bloginfo('name'))))) . '-backup';
		$filtered_array = array();
		for ($i = 0; $i < count($backup_files); $i++) {
			$backup_from = explode('_', $backup_files[$i]['name']);
			if (rawurldecode($this->normalize_string(strtolower(trim($backup_from[0])))) == $blog_name) {
				$filtered_array[] = $backup_files[$i];
			}
		}
		return $filtered_array;
	}

	function upload_max_filesize() {
		if (!$filesize = ini_get('upload_max_filesize')) {
			$filesize = "5M";
		}

		if ($postsize = ini_get('post_max_size')) {
			return min($this->get_byte_size($filesize), $this->get_byte_size($postsize));
		} else {
			return $this->get_byte_size($filesize);
		}
	}

	function get_byte_size($size = 0) {
		if (!$size) {
			return 0;
		}

		$scan['gb'] = 1073741824; //1024 * 1024 * 1024;
		$scan['g'] = 1073741824; //1024 * 1024 * 1024;
		$scan['mb'] = 1048576;
		$scan['m'] = 1048576;
		$scan['kb'] = 1024;
		$scan['k'] = 1024;
		$scan['b'] = 1;

		foreach ($scan as $unit => $factor) {
			if (strlen($size) > strlen($unit) && strtolower(substr($size, strlen($size) - strlen($unit))) == $unit) {
				return substr($size, 0, strlen($size) - strlen($unit)) * $factor;
			}
		}
		return $size;
	}

	function get_size_in($unit = 'm', $size= 0) {
		if (!$size) {
			return 0;
		}

		$scan['gb'] = 1073741824; //1024 * 1024 * 1024;
		$scan['g'] = 1073741824; //1024 * 1024 * 1024;
		$scan['mb'] = 1048576;
		$scan['m'] = 1048576;
		$scan['kb'] = 1024;
		$scan['k'] = 1024;
		$scan['b'] = 1;

		return round($size / $scan[$unit], 2);
	}

	function current_service() {

		$preferences = get_option('pressexpress_preferences');

		$PS3 = $preferences['pressexpress']['s3'];
		$PPRO = $preferences['pressexpress']['pressbackuppro'];
		$PDBOX = $preferences['pressexpress']['dropbox'];
		$PLOCAL = $preferences['pressexpress']['local'];

		if ($PS3['credential']) {
			return $this->service('S3');
		}

		if ($PDBOX['credential']) {
			return $this->service('dBox');
		}

		if ($PPRO['credential'] && !$PDBOX['credential']) {
			return $this->service('Pro');
		}

		if ($PLOCAL['path']) {
			return $this->service('Local');
		}

		return false;
	}


	function service($id=null) {

		$preferences = get_option('pressexpress_preferences');

		$PS3 = $preferences['pressexpress']['s3'];
		$PPRO = $preferences['pressexpress']['pressbackuppro'];
		$PDBOX = $preferences['pressexpress']['dropbox'];
		$PLOCAL = $preferences['pressexpress']['local'];

		$pref = array (

			'S3' => array(
				'id' => 'S3',
				'name' => 'Amazon S3',
				'credentials' => $PS3['credential'],
				'bucket_name' => $PS3['bucket_name'],
				'region' => $PS3['region'],
			),

			'dBox' => array(
				'id' => 'dBox',
				'name' => 'Dropbox',
				'credentials' => $PDBOX['credential'],
				'credentials_pro' => $PPRO['credential'],
			),

			'Pro' => array(
				'id' => 'Pro',
				'name' => 'PressBackup Pro',
				'credentials' => $PPRO['credential'],
			),

			'Local' => array(
				'id' => 'Local',
				'name' => 'Local Host',
				'path' => $PLOCAL['path'],
			),

		);


		if ( $id ) {
			return $pref[$id];
		} else {
			return $pref;
		}
	}

	function timezone_string () {
		if( !$val = get_option( 'timezone_string' )){
			$val = ceil(get_option( 'gmt_offset' ));
			$val = ( $val < 0)? str_replace('-', '+', $val):'-'.ltrim(str_replace('+', '-', $val), '-');
			$val = 'Etc/GMT'.$val;
		}
		return $val;
	}

	function normalize_string($string){
		$string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');
		$string = preg_replace('~&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1', htmlentities($string, ENT_QUOTES, 'UTF-8'));
		$string = str_replace('.','',$string);
		return $string;
	}

	function getCoxProtocol() {
		$scheme = 'http';
		if (isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'] == 'on') {
			$scheme .= 's';
		}
		return $scheme;
	}

	function check_log_file($log_file){
		//tools
		global $pressexpress;

		if ( file_exists( $log_file_path = $pressexpress->Path->Dir['LOGTMP']. DS. $log_file) ){
			return $log_file_path;
		}
		return false;
	}

	function get_log_file($log_file){

		if ( $file=$this->check_log_file($log_file) ) {
			return file_get_contents($file);
		}
		return null;
	}
}
?>
