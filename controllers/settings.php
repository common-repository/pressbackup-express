<?php

/**
 * Settings Controller for Pressbackup.
 *
 * This Class provide a interface to display and manage Plugin settings
 * Also Manage the configurattion wizard
 *
 * Licensed under The GPL v2 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @link				http://pressbackup.com
 * @package		controlers
 * @subpackage	controlers.settings
 * @since			0.1
 * @license			GPL v2 License
 */
class PressExpressSettings {

	/**
	 * Redirect the user to de correct page
	 * If the user has not configured the plugin
	 * this function redirect directly to the config init page
	 *
	 * Note: this function is automaticaly called
	 * by pressing on tool menu link
	 */
	function index() {
		global $pressexpress;
		$preferences = get_option('pressexpress_preferences');

		if ($preferences['pressexpress']['configured']) {
			$pressexpress->redirect(array('menu_type' => 'tools', 'controller' => 'principal', 'function' => 'dashboard'));
		}

		$pressexpress->redirect(array('controller' => 'settings', 'function' => 'config_init'));
	}

// Config Pages ( wizard )
//------------------------------

	/**
	 * Start The configuration wizard
	 * Check all that all nedded libs are intalled
	 * Also check for perms on tmp folders
	 */
	function config_init() {

		global $pressexpress;
		$pressexpress->View->layout('principal');
		$preferences = get_option('pressexpress_preferences');

		//misc
		$pressexpress->import('misc.php');
		$misc = new PressExpressMisc();

		$error = array();
		$disable_backup_files = false;

		//check for
		if (strpos($_SERVER['SERVER_SOFTWARE'], 'iis') !== false) {
			$error['iss'] = __('Sorry your web Server (Windows IIS) is not complatible with Pressbackup. You are welcome to use Pressbackup, but we cannot provide any support or guarantees for your system','pressbackup');
		}

		//check for
		if (ini_get('safe_mode')) {
			$error['safe_mode'] = __('Safe mode is enabled. You would need to contact your hosting provider and ask them if it\'s  necessary for you to have safe_mode enabled. Most hosts should be able  to disable it for you, or give you a more specific answer','pressbackup');
			$disable_backup_files = true;
		}

		//check for Zip Creation
		$bin = $misc->checkShell('zip');
		if (!$bin) {
			$preferences['pressexpress']['compatibility']['zip'] = 10;
			update_option('pressexpress_preferences', $preferences);
		}
		if (!$bin && !class_exists('ZipArchive')) {
			$error['zip'] = __('You probably don\'t have the php-zip extension installed. You would need to contact your hosting provider and ask them to install it.','pressbackup');
			$disable_backup_files = true;
		}

		//check for
		if (!class_exists('SimpleXMLElement')) {
			$error['xml'] = __('You probably don\'t have the SimpleXML extension installed.  You would need to contact your hosting provider and ask them to install it.','pressbackup');
			$disable_backup_files = true;
		}

		// Check for CURL
		if (!extension_loaded('curl')) {
			$error['curl'] = __('You probably don\'t have the php-curl extension installed.  You would need to contact your hosting provider and ask them to install it.','pressbackup');
			$disable_backup_files = true;
		}
		
		// Check CURL local
		$pressexpress->import('curl.php');
		$curl = new PressExpressCurl();

		$args = array(
			'url' => get_bloginfo( 'wpurl' )
		);
		$curl->call($args);

		if($curl->response_code != 200) {
			$error['curl'] = __('You probably don\'t have permission to use cURL to Localhost.  You would need to contact your hosting provider and ask them to enable this feature.','pressbackup');
			$disable_backup_files = true;
		}

		//tmp dir
		if (!file_exists($pressexpress->Path->Dir['PBKTMP']) && !mkdir($pressexpress->Path->Dir['PBKTMP'])) {
			$error['tmpdir'] = sprintf(__('Could not create the FileStore directory "%1$s". Please check the effective permissions.','pressbackup'), $pressexpress->Path->Dir['PBKTMP']);
			$disable_backup_files = true;
		} else {
			@chmod($pressexpress->Path->Dir['PBKTMP'], 0777);
		}

		//log dir
		if (!file_exists($pressexpress->Path->Dir['LOGTMP']) && !mkdir($pressexpress->Path->Dir['LOGTMP'])) {
			$error['logdir'] = sprintf(__('Could not create the FileStore directory "%1$s". Please check the effective permissions.','pressbackup'), $pressexpress->Path->Dir['LOGTMP']);
			$disable_backup_files = true;
		} else {
			@chmod($pressexpress->Path->Dir['LOGTMP'], 0777);
		}

		//validate backgroun process creation
		$checkCron = get_option('pressexpress_wizard_cron_state');
		if ($checkCron == "fail") {

			update_option('pressexpress_wizard_cron_state', 'not tested');
			$error['cron'] = __('Could not create a Cron Job Backup. Try it Again refreshing the page, or contact Pressbackup Support.');
			$disable_backup_files = true;
		} 
		elseif (!$disable_backup_files && $checkCron  != "success" ){

			$pressexpress->import('backup_functions.php');
			$pb = new PressExpressBackup();
			$pb->add_schedule('+2 seconds', 'pressexpress_wizard_cron_task');

			$pressexpress->View->set('load_check_wizard_cron', true);
		}

		//set errors
		if($pressexpress->Session->check('error_cron') ){
			$error['cron'] = $pressexpress->Session->read('error_cron');
			$pressexpress->Session->delete('error_cron');
		}

		$pressexpress->View->set('error', $error);
		$pressexpress->View->set('disable_backup_files', $disable_backup_files);

	}

	// Config save functions
	//------------------------------

	/**
	 * Store the credentials if they are correct
	 * or save the error and return to form page
	 */
	function config_step1_save() {

		global $pressexpress;
		$preferences = get_option('pressexpress_preferences');

		$pressexpress->import('Pro.php');

		$pro = new PressExpress();
		$response = $pro->authExpress();

		if(!$response) {
			$pressexpress->Session->write('error_cron', __('Please register your site in https://pressbackup.com'));
			$pressexpress->redirect(array('controller'=>'settings', 'function'=>'config_init')); exit;
		}

		/**
		 * Config Default Presbackup Express
		 */

		$PPRO = array(
			'user' => $response['items'][0]['item']['username'],
			'key' => urldecode($response['items'][0]['item']['authkey'])
		);

		$preferences['pressexpress']['pressbackuppro']['credential'] = base64_encode($PPRO['user'] . '|AllYouNeedIsLove|' . $PPRO['key']);
		update_option('pressexpress_preferences', $preferences);

		$this->config_step2_save();
	}

	/**
	 * Store the settings if they are correct
	 * or save the error and return to form page
	 */
	function config_step2_save() {

		global $pressexpress;
		$preferences = get_option('pressexpress_preferences');

		$pressexpress->import('backup_functions.php');
		$pb = new PressExpressBackup();

		$pressexpress->import('misc.php');
		$misc = new PressExpressMisc();

		/**
		 * Config Default Pressbackup Express
		 */
		$preferences['pressexpress']['backup']['copies'] = 7;
		$preferences['pressexpress']['backup']['time'] = 24;
		$preferences['pressexpress']['backup']['type'] = '7,5,3,1';

		if($misc->getCoxProtocol() == 'https'){
			$preferences['pressexpress']['compatibility']['background'] = 20;
		}

		$preferences['pressexpress']['configured'] = 1;

		update_option('pressexpress_preferences', $preferences);

		$pb->add_schedule('+2 seconds');
		sleep(2);

		$pressexpress->import('Pro.php');
		$credentials=explode('|AllYouNeedIsLove|', base64_decode( $preferences['pressexpress']['pressbackuppro']['credential'] ));
		$pro = new PressExpress($credentials[0], $credentials[1]);
		$pro->wasConfigured();

		$pressexpress->redirect(array('menu_type' => 'tools', 'controller' => 'principal', 'function' => 'dashboard'));
	}

	function wizard_cron_task (){

		update_option('pressexpress_wizard_cron_state', 'success');
	}

	function wizard_cron_fail () {

		global $pressexpress;
		update_option('pressexpress_wizard_cron_state', 'fail');
		$pressexpress->redirect(array('controller'=>'settings', 'function'=>'config_init'));
	}

	function wizard_cron_status ()
	{
		$response = get_option('pressexpress_wizard_cron_state');

		if ($response == 'success') {
			exit('{ "status": "ok"}');
		}

		exit('{ "status": "fail" }');
	}

}
?>
