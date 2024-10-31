<?php

 /**
 * Init script for FramePress.
 *
 * This script is responsable of prepare all needed info and perform pre function to strart de framework
 *
 * Licensed under The GPL v2 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @link				none yet
 * @package       core
 * @subpackage    core.init
 * @since         0.1
 * @license       GPL v2 License
 */

	/**
	 * Use the DS to separate the directories in other defines
	*/
	if(!defined('DIRECTORY_SEPARATOR')){define('DIRECTORY_SEPARATOR', '/');}
	if(!defined('DS')){define('DS', DIRECTORY_SEPARATOR);}

	/**
	 * Create a custom error handler
	*/
	if (!function_exists('w2pf_eh')){
		function w2pf_eh($level, $message, $file, $line, $context) {
			//Handle user errors, warnings, and notices ourself
			if($level === E_USER_ERROR || $level === E_USER_WARNING || $level === E_USER_NOTICE) {
				echo '<div style="font-size:13px;"><b style="color:#565656;">Press</b><b style="color:#007cd1;">Backup</b>: ' .$message . '</div>';
				return(false); //And prevent the PHP error handler from continuing
			}
			return(false); //Otherwise, use PHP's error handler
		}
	}

	/**
	 * Start with check proccess
	*/
	set_error_handler('w2pf_eh');


		/**
		 * Load User custom configurations. It also has the prefix to rename all core classes
		*/
		$fp_config=array();
		require_once(dirname(dirname(__FILE__)) . DS . 'config' . DS . 'config.php');

		/**
		 * Check if config file has prefix setted
		*/
		if(!$fp_config['prefix']){
			trigger_error("Please change the value of prefix, on <b>config/config.php</b>", E_USER_WARNING);
		}
		else
		{

			$fp_temp='';
			if ($fp_config['use.tmp']) {

				/**
				 * Check if we can use sys tmp folder
				*/
				if ( !function_exists('sys_get_temp_dir'))
				{
					if( $fp_temp=getenv('TMP') ){} elseif( $fp_temp=getenv('TEMP')){} elseif( $fp_temp=getenv('TMPDIR')){}
				}
				elseif(@realpath(sys_get_temp_dir()))
				{
					$fp_temp=realpath(sys_get_temp_dir());
				}

				/*
					If not, we need to get the perms to write on our tmp folder
					*this can be commented if the plugin don't need to use a temp folder
				*/
				if(!$fp_temp && substr(sprintf('%o', fileperms(dirname(dirname(__FILE__)) . DS . 'tmp')), -3) < '777'){
					trigger_error("Can&#39;t write on <b>". dirname(dirname(__FILE__)) . DS . 'tmp'."</b> folder, please change it's permissions to 777", E_USER_WARNING);
				}
			}

			/**
			 * Rename core classes to get unique names for this plugin
			*/
			$fp_directoryHandle = opendir($fp_directory = dirname(__FILE__));
			while ($fp_contents = readdir($fp_directoryHandle)) {
				if(!preg_match("/^w2pf_init.php$/", $fp_contents) && !preg_match("/^(.)*~$/", $fp_contents) && $fp_contents != '.' && $fp_contents != '..' && !is_dir($fp_directory . DS .$fp_contents)) {
					$fp_code = file_get_contents($fp_directory . DS . $fp_contents);
					preg_match('/class ([a-z0-9]*)_([a-z]*)_(?P<version>\w+)/', $fp_code, $fp_m);
					if ("{$fp_m[1]}_{$fp_m[2]}_{$fp_m['version']}" != "{$fp_m[1]}_{$fp_m[2]}_{$fp_config['prefix']}"){
						$fp_code = str_replace("{$fp_m[1]}_{$fp_m[2]}_{$fp_m['version']}", "{$fp_m[1]}_{$fp_m[2]}_{$fp_config['prefix']}", $fp_code);
						file_put_contents($fp_directory . DS . $fp_contents, $fp_code);
					}
				}
			}

			/**
			 * Save some globals to use them to create core class properly
			*/
			$GLOBALS["FramePress"]='w2pf_core_'.$fp_config['prefix'];
			$GLOBALS["FP_CONFIG"]=$fp_config;
			$GLOBALS["FP_SYS_TEMP"]=$fp_temp;

			/**
			 * Chek if class with prefix changed already exist (the neme will be not unique)
			*/
			global $FramePress;
			if (class_exists($FramePress)) {
				trigger_error("Sorry prefix: <b>" . $fp_config['prefix'] . "</b> is used by other plugin on this WP installation, please choose a unique one", E_USER_WARNING);
			}

		}

	restore_error_handler();

	require_once('w2pf_core.php');

?>
