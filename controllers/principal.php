<?php
 /**
 * Principal Controller for Pressbackup Express.
 *
 * This Class provide a interface to display and manage backups
 *
 * Licensed under The GPL v2 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @link				http://pressbackup.com
 * @package		controlers
 * @subpackage	controlers.principal
 * @since			0.1
 * @license			GPL v2 License
 */

class PressExpressPrincipal {

	/**
	 * Redirect the user to de correct page
	 * If the user has not configured the plugin
	 * this function redirect directly to the config init page
	 *
	 * Note: this function is automaticaly called
	 * by pressing on tool menu link
	*/
	function index(){
		global $pressexpress;

		$preferences= get_option('pressexpress_preferences');

		if(!$preferences['pressexpress']['configured']) {
			$redirect=array('menu_type'=>'settings', 'controller'=>'settings', 'function'=>'config_init');
		} else {
			$redirect=array('controller'=>'principal', 'function'=>'dashboard');
		}

		$pressexpress->redirect($redirect);
	}

// Pages
//------------------------------

	function dash_options($from = null, $page = null, $remove_schedule=false ){
		global $pressexpress;

		if( $page ){
			$pressexpress->Session->write('dash.page', $page);
		}

		if( $from ) {

			$saved_from = ($saved_from = $pressexpress->Session->read('dash.from'))?$saved_from:'this_site';
			$pressexpress->Session->write('dash.from', $from);

			// if tab is different of the previos saved, show the first page
			if( $saved_from != $from ){
				$pressexpress->Session->write('dash.page', 1);
			}
		}

		if( $remove_schedule ){
			$this->remove_scheduledjobs();
		}

		$pressexpress->redirect(array('controller'=>'principal', 'function'=>'dashboard'));
	}


	/**
	 * Shows dashboard page (the main page)
	 * This page has the interface to manage backups
	 *
	 * @from string: what tab of backups see
	 * @reload bool: load ajax checker
	 * @page integer: page of backup to see
	 */
	function dashboard ()
	{
		global $pressexpress;
		$pressexpress->View->layout('principal');
		$preferences= get_option('pressexpress_preferences');
		$pressexpress->import('misc.php');
		$misc = new PressExpressMisc();


		$pressexpress->import('backup_functions.php');
		$pb = new PressExpressBackup();

		//set the type of service use to get/show backups
		$service = $misc->current_service();

		//check what dashboard we are seeing last time
		$from = ($saved_from = $pressexpress->Session->read('dash.from'))?$saved_from:'this_site';

		//load ajax to check backups status?
		//load on cron or on backup now status
		$reload = ( $misc->get_log_file('create.log') == 'start' ||  $misc->get_log_file('sent.log')  == 'start' )?true:false; 

		$reload = ($saved_reload = $pressexpress->Session->read('dash.reload'))?$saved_reload:$reload;
		$pressexpress->Session->delete('dash.reload');

		//init
		$bucket_files= array();

		$pressexpress->import('Pro.php');
		$credentials=explode('|AllYouNeedIsLove|', base64_decode( $service['credentials'] ));
		$pbp = new PressExpress($credentials[0], $credentials[1]);
		$bucket_files = $pbp->getFilesList();

		if (!$bucket_files) {
			$pressexpress->View->set('bucket_files', array());
		}

		//inicializacion del paginador
		else {

			//get a normalized list and sorted by date
			$bucket_files= @$misc->msort($service['id'], $bucket_files);
			$bucket_files= $misc->filter_files ($from, $bucket_files);

			$pressexpress->import('paginator.php');
			$pag = new PressExpressPaginator();
			$pagination_size = 5;

			$page = ($page_saved = $pressexpress->Session->read('dash.page'))?$page_saved:1;

			if(!array_slice($bucket_files, (($page -1 )*$pagination_size), $pagination_size, true) && $page > 1){
				$page--;
				$pressexpress->Session->write('dash.page', $page);
			}

			$paginator['page'] = $page ;
			$paginator['total']  = count($bucket_files);
			$paginator['pages'] = ceil ($paginator['total'] /$pagination_size);
			$paginator['ini']  = (($page -1 )*$pagination_size);
			$paginator['fin']  = (($page*$pagination_size) -1);
			$paginator['func_path']  = array('controller'=>'principal', 'function'=>'dash_options', $from);
			$paginator['pagination'] = $pagination_size;
			$pressexpress->View->set('paginator', $pag->get_html($paginator));
			$pressexpress->View->set('bucket_files', array_slice($bucket_files, $paginator['ini'], $pagination_size, true));
		}

		//inform about the first time backup
		if(get_option('pressexpress_first_run') == true ) {
			update_option('pressexpress_first_run', false);
			$pressexpress->Msg->general('We are processing a first backup, if you do not see a progress bar you can refresh the page');
		}

		//set messages
		if($pressexpress->Session->check('general_msg') || $pressexpress->Session->check('general_msg', true)){
			$pressexpress->Msg->general($pressexpress->Session->read('general_msg'));
			$pressexpress->Msg->general($pressexpress->Session->read('general_msg', true));
			$pressexpress->Session->delete('general_msg');
			$pressexpress->Session->delete('general_msg', true);
		}

		//set errors
		if($pressexpress->Session->check('error_msg') || $pressexpress->Session->check('error_msg', true)){
			$pressexpress->Msg->error($pressexpress->Session->read('error_msg'));
			$pressexpress->Msg->error($pressexpress->Session->read('error_msg', true));
			$pressexpress->Session->delete('error_msg');
			$pressexpress->Session->delete('error_msg', true);
		}

		$backup_types = explode(',',$preferences['pressexpress']['backup']['type']);




		$pressexpress->View->set('from', $from);
		$pressexpress->View->set('settings', $preferences);
		$pressexpress->View->set('backup_types',$backup_types);
		$pressexpress->View->set('service', $service);
		$pressexpress->View->set('reload', $reload);
		$pressexpress->View->set('timezone_string', $misc->timezone_string());
		$pressexpress->View->set('next_scheduled_job', wp_next_scheduled('pressexpress_backup_start_cronjob'));
	}

	/**
	 * Shows upload form for restore a PC soter backup
	 */
	function backup_upload_page ()
	{
		//tools & info
		global $pressexpress;
		$pressexpress->import('misc.php');
		$pressexpress->View->layout('principal');
		$preferences= get_option('pressexpress_preferences');
		$misc = new PressExpressMisc();

		//set error from previus upload
		if($pressexpress->Session->check('error_msg')){
			$pressexpress->Msg->error($pressexpress->Session->read('error_msg'));
			$pressexpress->Session->delete('error_msg');
		}

		//check for folder permissions
		$permissions = array();
		$disable = false;
		$msg = false;

		$dir = WP_CONTENT_DIR . DS;
		if( !@is_writable($dir) ) { $permissions[] ='wp_content'; }
		if( !@is_writable($dir . 'themes' . DS ) ) { $permissions[] ='themes'; }
		if( !@is_writable($dir . 'plugins' . DS ) ) { $permissions[] ='plugins'; }
		if( @file_exists($dir . 'uploads' ) && !@is_writable($dir . 'uploads' . DS ) ) { $permissions[] ='uploads'; }


		if($permissions) {
			$denied_perms="<b>".join('</b>, <b>', $permissions)."</b>";
			$msg = sprintf(__('Permissions denied to write on %1$s folder/s','pressbackup'),$denied_perms);
			$msg .= "<br/> ";
			$msg .= __('Pleace change the permissions of those folders to 777','pressbackup');
			$pressexpress->Msg->error($msg);
			$disable = true;
		}

		$pressexpress->View->set('upload_size', $misc->get_size_in('m', $misc->upload_max_filesize()));
		$pressexpress->View->set('settings', $preferences);
		$pressexpress->View->set('disable_ulpoad', $disable);
	}


// Backup functions: create
//------------------------------

	/**
	 * Start a manual backup
	 * create a schedule action (background proccess)
	 *
	 * @reload_type string: specify what type of manual backup we are doing
	 */
	function backup_start ($reload_type="backup_download", $to_include=null) {

		//tools
		global $pressexpress;
		$preferences = get_option('pressexpress_preferences');

		$pressexpress->import('backup_functions.php');
		$pb = new PressExpressBackup();

		$pressexpress->import('curl.php');
		$curl = new PressExpressCurl();

		$pressexpress->import('misc.php');
		$misc = new PressExpressMisc();

		$pb->remove_schedule('pressexpress_backupnow_download');
		$pb->remove_schedule('pressexpress_backupnow_save');

		//clean log && tmp dir
		$misc->perpare_folder($pressexpress->Path->Dir['LOGTMP']);
		$misc->perpare_folder($pressexpress->Path->Dir['PBKTMP']);

		if($to_include){
			$pressexpress->Session->write('type_temp',$to_include,true);
		}

		switch($preferences['pressexpress']['compatibility']['background']){
			case 10://soft

				if ($reload_type == "backup_download") {
					$action = 'pressexpress_backupnow_download';
				}
				elseif ($reload_type == "dashboard") {
					$action = 'pressexpress_backupnow_save'; 
				}
				$pb->add_schedule('+4 seconds', $action);

			break;
			case 20: //Medium

				if ($reload_type == "backup_download") {
					$action = get_bloginfo('wpurl').'/wp-admin/admin-ajax.php?action=pressexpress_backupnow_download_ajax';
				}
				elseif ($reload_type == "dashboard") {
					$action = get_bloginfo('wpurl').'/wp-admin/admin-ajax.php?action=pressexpress_backupnow_save_ajax';
				}

				$args = array(
					'url' => $action,
					'cookie'=>$_COOKIE,
					'timeout'=>4,
				);
				$curl->call($args);

			break;
			case 30: //Hard
				if ($reload_type == "backup_download") {
					$action = get_bloginfo('wpurl').'/wp-admin/admin-ajax.php?action=pressexpress_backupnow_download_ajax';
				}
				elseif ($reload_type == "dashboard") {
					$action = get_bloginfo('wpurl').'/wp-admin/admin-ajax.php?action=pressexpress_backupnow_save_ajax';
				}

				$cookie = array(); foreach($_COOKIE as $key => $value) { $cookie[]=$key.'='.$value;}

				$info = array();
				$info[0] = $action;
				$info[1]= join (';', $cookie);

				$args = array(
					'file' => $pressexpress->Path->Dir['LIB'].DS.'background.php',
					'split'=>'ALLYOUNEEDISLOVE',
					'args'=>base64_encode(join('|ALLYOUNEEDISLOVE|', $info)),
				);
				$misc->php($args);

			break;
		}

		$pressexpress->Session->write('dash.reload', $reload_type);
		$pressexpress->redirect(array('controller'=>'principal', 'function'=>'dashboard'));
		exit();
	}

	/**
	 * Create and send a backup to S3 or PPro
	 * this is the background proccess called from
	 * backup start or via scheduled task (cron job)
	 */
	function backup_create_and_send() {
		@ignore_user_abort(true);
		@set_time_limit(0);
		@ob_end_clean();
		@ob_start();
		header("Status: 204", true);
		header("Content-type: text/html", true);
		header("Content-Length: 0", true);
		header("Connection: close", true);
		@ob_end_flush();
		@ob_flush();
		@flush();
		@fclose(STDIN);
		@fclose(STDOUT);
		@fclose(STDERR);

		//tools
		global $pressexpress;
		$preferences= get_option('pressexpress_preferences');

		$pressexpress->import('backup_functions.php');
		$pb = new PressExpressBackup();

		$pressexpress->import('misc.php');
		$misc = new PressExpressMisc();

		$is_cron = !$pressexpress->Session->check('type_temp',true);

		if ( $is_cron && $preferences['pressexpress']['backup_advanced']['activated'] ){
			$jobs_to_do = $preferences['pressexpress']['backup']['type'] = $pb->find_something_to_do($preferences['pressexpress']['backup_advanced']);
			update_option('pressexpress_preferences', $preferences); 
		}

		if(!$is_cron){
			$aux_type = $preferences['pressexpress']['backup']['type'];
			$preferences['pressexpress']['backup']['type'] = $pressexpress->Session->read('type_temp',true);
			update_option('pressexpress_preferences', $preferences);
		}

		//get the type of credential
		$service = $misc->current_service();

		@wp_clear_scheduled_hook('pressexpress_backupnow_save');

		set_error_handler(array($this, 'creation_eh'));

		if(!$file = $pb->create()) { exit(); }

		$pb->save_on($service['id'], $file);

		restore_error_handler();

		@wp_clear_scheduled_hook('pressexpress_backupnow_save');

		$misc->perpare_folder($pressexpress->Path->Dir['PBKTMP']);

		if($is_cron && $preferences['pressexpress']['backup_advanced']['activated']){
			$preferences['pressexpress']['backup']['type'] = $pb->activated_types();
			update_option('pressexpress_preferences', $preferences); 
			$pb->update_last_date($jobs_to_do);
		}

		if($is_cron){
			$pb->add_schedule();
		}

		if(!$is_cron){
			$preferences['pressexpress']['backup']['type'] = $aux_type;
			$pressexpress->Session->delete('type_temp',true);
			update_option('pressexpress_preferences', $preferences);
		}

		exit;
	}

	/**
	 * Create a backup to downloading it latter
	 * this is the background proccess called from
	 * backup start. The ajax checker tell de browser when
	 * this procces finish and redirect the user to donwload backup
	 */
	function backup_create_then_download () {
		@set_time_limit(0);

		//tools
		global $pressexpress;
		$preferences= get_option('pressexpress_preferences');

		$pressexpress->import('backup_functions.php');
		$pb = new PressExpressBackup();

		@wp_clear_scheduled_hook('pressexpress_backupnow_download');

		if($pressexpress->Session->check('type_temp',true)){
			$aux_type = $preferences['pressexpress']['backup']['type'];
			$preferences['pressexpress']['backup']['type'] = $pressexpress->Session->read('type_temp',true);
			update_option('pressexpress_preferences', $preferences);
		}

		set_error_handler(array($this, 'creation_eh'));

		if(!$zip_file_created = $pb->create()){ exit; }
		restore_error_handler();

		@wp_clear_scheduled_hook('pressexpress_backupnow_download');

		@file_put_contents($pressexpress->Path->Dir['LOGTMP'] . DS . 'create.return', $zip_file_created);

		if($pressexpress->Session->check('type_temp',true)){
			$preferences['pressexpress']['backup']['type'] = $aux_type;
			$pressexpress->Session->delete('type_temp',true);
			update_option('pressexpress_preferences', $preferences);
		}

		exit;
	}

// Backup functions: Download
//------------------------------

	/**
	 * Download a backup
	 * the backup was presviously stored by backup_create_then_download
	 */
	function backup_download ($file =null){
		global $pressexpress;
		@ob_end_clean();
		$pressexpress->View->layout('blank');
		$pressexpress->import('download.php');
		$pressexpress->View->set('file', base64_decode($file));
		register_shutdown_function (array($this, 'clean'));
	}

// Backup functions: Upload
//------------------------------

	/**
	 * Call to restore if uploaded backups its valid
	 * or save the errors and display upload form again
	 */
	function backup_upload ($then= 'restore')
	{
		//tools & info
		global $pressexpress;
		$preferences= get_option('pressexpress_preferences');
		$pressexpress->import('backup_functions.php');

		//return if exists upload errors
		if(!$this->check_backup_upload() || !$this->check_backup_integrity($_FILES['backup']['tmp_name']))
		{
			$pressexpress->redirect(array('controller'=>'principal', 'function'=>'backup_upload_page'));
		}

		//create backup file
		$pb = new PressExpressBackup();
		$pb->restore(array('tmp_name'=>$_FILES['backup']['tmp_name']));


		//delete files from tmp folder
		$pressexpress->import('misc.php');
		$misc = new PressExpressMisc();
		$misc->perpare_folder($pressexpress->Path->Dir['PBKTMP']);

		$pressexpress->redirect(array('controller'=>'principal', 'function'=>'dashboard'));
	}

// Backup functions: Restore
//------------------------------

	/**
	 * Restore a backup previously stored by backup_upload
	 * or  S3/PPro get function ( from backup function lib)
	 */
	function backup_restore ($name= null)
	{
		//tools & info
		global $pressexpress;
		$preferences= get_option('pressexpress_preferences');
		$pressexpress->import('backup_functions.php');

		//check for folder permissions
		$permissions = array ();
		$dir = WP_CONTENT_DIR . DS;
	
		if( !@is_writable($dir) ) { $permissions[] ='wp_content'; }
		if( !@is_writable($dir . 'themes' . DS ) ) { $permissions[] ='themes'; }
		if( !@is_writable($dir . 'plugins' . DS ) ) { $permissions[] ='plugins'; }
		if( @file_exists($dir . 'uploads' ) && !@is_writable($dir . 'uploads' . DS ) ) { $permissions[] ='uploads'; }

		if($permissions) {
			$denied_perms="<b>".join('</b>, <b>', $permissions)."</b>";
			$msg = sprintf(__('Permissions denied to write on %1$s folder/s','pressbackup'),$denied_perms);
			$msg .= "<br/> ";
			$msg .= __("Please, change the permissions of those folders to 777 if you want to be able to restore a backup",'pressbackup');
			$pressexpress->Session->write( 'error_msg', $msg);
			$pressexpress->redirect(array('controller'=>'principal', 'function'=>'dashboard')); exit;
		}

		$pb = new PressExpressBackup();
		if(!$tmp_name=$pb->get($name))
		{
			$pressexpress->redirect(array('controller'=>'principal', 'function'=>'dashboard'));
		}
		elseif(!$this->check_backup_integrity($tmp_name))
		{
			$pressexpress->redirect(array('controller'=>'principal', 'function'=>'dashboard'));
		}

		$pb->restore(array('tmp_name'=>$tmp_name));

		//delete files from tmp folder
		$pressexpress->import('misc.php');
		$misc = new PressExpressMisc();
		$misc->perpare_folder($pressexpress->Path->Dir['PBKTMP']);

		$pressexpress->redirect(array('controller'=>'principal', 'function'=>'dashboard'));
	}

// Backup functions: Get
//------------------------------

	/**
	 * Download a backup From S3
	 *
	 * Download a backup previously stored by
	 * S3 get function ( from backup function lib)
	 */
	function backup_get ($name=null)
	{
		//tools & info
		global $pressexpress;
		$preferences= get_option('pressexpress_preferences');
		$pressexpress->import('backup_functions.php');

		$pb = new PressExpressBackup();
		if(!$tmp_name=$pb->get($name))
		{
			$pressexpress->redirect(array('controller'=>'principal', 'function'=>'dashboard'));
		}
		elseif(!$this->check_backup_integrity($tmp_name))
		{
			$pressexpress->redirect(array('controller'=>'principal', 'function'=>'dashboard'));
		}

		ob_end_clean();
		register_shutdown_function (array($this, 'clean'));
		$pressexpress->import('download.php');
		$pressexpress->View->set('file', $tmp_name);
	}

	/**
	 * Download a backup From PPro
	 *
	 * Prepare the backup for download on PPro server
	 *  then redirect the user to there to download the backup
	 */
	function backup_get2 ($name=null)
	{
		//tools & info
		global $pressexpress;
		$preferences= get_option('pressexpress_preferences');
		$pressexpress->import('backup_functions.php');

		$pb = new PressExpressBackup();
		if(!$tmp_name=$pb->get2($name))
		{
			$pressexpress->redirect(array('controller'=>'principal', 'function'=>'dashboard'));
		}

		$pressexpress->redirect('http://pressbackup.com/pro/api/download/'.base64_encode($name));
	}

// Backup functions: Delete
//------------------------------

	/**
	 * Remove a backup From PPro/S3
	 *
	 * @name string: name of the backup to remove
	 */
	function backup_delete ($name=null)
	{
		//tools & info
		global $pressexpress;
		$preferences= get_option('pressexpress_preferences');
		$pressexpress->import('backup_functions.php');

		$pb = new PressExpressBackup();
		$pb->delete($name);
		$pressexpress->redirect(array('controller'=>'principal', 'function'=>'dashboard'));
	}


// Functions helpers
//------------------------------

	/**
	 * Check backup upload
	 *
	 * Check if the upload of a backup was made correctly
	 * ej: see if no errors, or if uploaded file is a zip file etc
	 */
	function check_backup_upload()
	{
		//tools
		global $pressexpress;
		$pressexpress->import('misc.php');
		$misc = new PressExpressMisc();

		if (!isset($_FILES['backup']) || $_FILES['backup']['error']==4)
		{
			$pressexpress->Session->write('error_msg', sprintf(__('You sent an empty file or it is bigger than %1$s Mb. Please try it again','pressbackup'), $misc->get_size_in('m', $misc->upload_max_filesize())));
			return false;
		}
		if ($_FILES['backup']['error']!=0)
		{
			$pressexpress->Session->write( 'error_msg', sprintf(__('There was a problem, maybe the file is bigger than %1$s Mb. Please try it again','pressbackup'),$misc->get_size_in('m', $misc->upload_max_filesize())));
			return false;
		}
		if ( !in_array($_FILES['backup']['type'], array('application/zip', 'application/x-zip-compressed')))
		{
			$pressexpress->Session->write( 'error_msg', sprintf(__('Wrong file type: %1$s','pressbackup'),$_FILES['backup']['type']));
			return false;
		}
		if (!is_uploaded_file($_FILES['backup']['tmp_name']))
		{
			$pressexpress->Session->write( 'error_msg', __('The file could not be uploaded correctly','pressbackup'));
			return false;
		}
		return true;
	}

	/**
	 * Check a backup integrity
	 *
	 * Check if the uploaded or geted backup
	 * can be opened without errors
	 */
	function check_backup_integrity($zip_file)
	{
		//tools
		global $pressexpress;

		if(!file_exists($zip_file)){
			$pressexpress->Session->write( 'error_msg', __('file not found','pressbackup'));
			return false;
		}

		$zip = new ZipArchive();
		if ($zip->open($zip_file) !== TRUE)
		{
			$pressexpress->Session->write( 'error_msg', __('Sorry, but the file is corrupt','pressbackup'));
			return false;
		}
		$zip->close();
		return true;
	}

	/**
	 * Check the status of the backup now process
	 *
	 * This function its called via Ajax and inform what
	 * is the process doing. And most important when
	 * the process finish.
	 */
	function check_backupnow_status()
	{
		//tools
		global $pressexpress;
		$pressexpress->import('misc.php');
		$misc = new PressExpressMisc();

		//for what task we are checking, download or save?
		$task = $_POST['task'];

		//posibilly background procees its not running
		$response = '{"action": "wait", "status": "fail"}';

		//its creating the backup
		if( ($logfilec = $misc->check_log_file('create.log')) && !$misc->check_log_file('sent.log') ) {

			$get = file_get_contents($logfilec);
			switch($get){
				case 'fail':
					@unlink($logfilec);
					$response = '{"action": "finish", "status": "fail"}';
				break;
				case 'finish':
					$rget='';if($returnfile = $misc->check_log_file('create.return') ) { $rget= file_get_contents( $returnfile ); }
					@unlink($logfilec); @unlink($returnfile);
					if($task == 'download') {
						$response = '{"action": "finish", "status": "ok", "response": { "file":"'.base64_encode($rget).'"}}';
					} else {
						$response = '{"action": "wait", "status": "ok", "task_now": "'.__('Start sending backup','pressbackup').'"}';
					}
				break;
				default: //creating
					$response = '{"action": "wait", "status": "ok", "task_now": "'.$get.'"}';
				break;
			}
		}

		//its sending the backup
		elseif( $logfiles = $misc->check_log_file('sent.log') ) {

			@unlink($logfilec);
			$get = file_get_contents($logfiles);
			switch($get){
				case 'fail':
					@unlink($logfiles); $pressexpress->Session->delete( 'sent.percent', true);
					$response = '{"action": "finish", "status": "fail"}';
				break;
				case 'finish':
					$rget=''; if( $returnfile = $misc->check_log_file('sent.return') ) { $rget= file_get_contents( $returnfile ); }
					@unlink($logfiles); @unlink($returnfile); $pressexpress->Session->delete( 'sent.percent', true);
					$response = '{"action": "finish", "status": "ok", "response": ""}';
				break;
				default: //sending
					$current_taskpercent = $pressexpress->Session->read( 'sent.percent', true);
					$percent= explode('|', $current_taskpercent);
					if(isset($percent[1])) {
						$task_now = __("Sending backup",'pressbackup')." - ".$misc->get_size_in('m', $percent[0]).' MB / '.$misc->get_size_in('m', $percent[1]).' MB'; 
						if(($percent[1] -$percent[0]) == 1){
							$task_now = __("Checking Integrity",'pressbackup');
						}
						$response = '{"action": "wait", "status": "percent", "task_now": "'.$task_now.'", "response":{ "total": "'.$percent[1].'", "current":"'.$percent[0].'"} }';
					}
				break;
			}
		}

		exit( $response);
	}

	/**
	 * Check if a log file exists
	 *
	 * This is a helper function for check_backupnow_status
	 */


	/**
	 * Background process error handler
	 *
	 * This function register the errors ocurred on the background process
	 */
	function creation_eh($level, $message, $file, $line, $context) {
		global $pressexpress;
		@file_put_contents($pressexpress->Path->Dir['LOGTMP'] . DS . 'create.fail', $message, FILE_APPEND);
		//@unlink($pressexpress->Path->Dir['LOGTMP'] . DS . 'create.log');
		return false;
	}

// Functions: tests
//------------------------------

	/**
	 * Shows host information && background status (for report an error)
	 */
	function host_info(){
		global $pressexpress;

		$pressexpress->import('misc.php');
		$misc = new PressExpressMisc();

		//
		// host info
		//

		$service = $misc->current_service();
		$service_type = (isset($service['id']))?$service['id']:'download';
		$plugin_info = get_plugin_data($pressexpress->main_file);

		$info = array(
			'Host'=> array(
				'modules' =>  get_loaded_extensions(),
				'type' => $_SERVER['SERVER_SOFTWARE'],
				'sapi' => php_sapi_name(),
				'port' => $_SERVER['SERVER_PORT'],
				'mem_max' => ini_get('memory_limit'),
				'mem_used' => memory_get_peak_usage(true),
				'tmp_dir' => $pressexpress->Path->Dir['SYSTMP'],
				'tmp_free' =>  disk_free_space($pressexpress->Path->Dir['SYSTMP']),
			),
			'User' => array(
				'browser' => $_SERVER['HTTP_USER_AGENT']
			),
			'WP'=> array(
				'version' => get_bloginfo ('version'),
				'url' => get_bloginfo ('wpurl'),
			),
			'Plugin'=> array(
				'version' => $plugin_info['Version'],
				'service' => $service_type,
			),
		);

		$pressexpress->View->set('info', $info);


		//background errors
		$errors = '';
		if ( $logfilef = $misc->check_log_file('create.fail') ) {
			$errors = nl2br(file_get_contents($logfilef));
		}

		//folder contents
		$tmp_dir_path = $pressexpress->Path->Dir['PBKTMP'] . DS;
		$tmp_dir = array(__('Folder was not created','pressbackup'));
		if(is_dir($tmp_dir_path)) {
			$tmp_dir = scandir($tmp_dir_path);
			for($i=0; $i < count($tmp_dir); $i++){
				if(in_array($tmp_dir[$i], array('.', '..'))){continue;}
				$tmp_dir[$i].= ' <br/>&nbsp;&nbsp;&nbsp;&nbsp;- '.
				__('size','pressbackup').': '.$misc->get_size_in('m', filesize ($tmp_dir_path.DS.$tmp_dir[$i]) ).' MB -'.
				$pressexpress->Html->link(__('download','pressbackup'), array('menu_type'=>'tools', 'controller'=>'principal', 'function'=>'backup_download', base64_encode($tmp_dir_path.DS.$tmp_dir[$i])));
			}
		}

		$log_dir_path = $pressexpress->Path->Dir['LOGTMP'] . DS;
		$log_dir = array(__('Folder was not created','pressbackup'));
		if(is_dir($log_dir_path)) { $log_dir = scandir($log_dir_path); }

		$pressexpress->View->set('tmp_dir', $tmp_dir);
		$pressexpress->View->set('log_dir', $log_dir);
		$pressexpress->View->set('error_log', $errors);

	}


	function clean ($redirect=null) {
		global $pressexpress;

		//misc
		$pressexpress->import('misc.php');
		$misc = new PressExpressMisc();

		//clean log && tmp dir
		$misc->perpare_folder($pressexpress->Path->Dir['LOGTMP']);
		$misc->perpare_folder($pressexpress->Path->Dir['PBKTMP']);

		if( $redirect ) {
			$pressexpress->redirect(array('controller'=>'principal', 'function'=>'host_info'));
		}
	}

	function remove_scheduledjobs ($redirect=null) {
		global $pressexpress;

		@wp_clear_scheduled_hook('pressexpress_backup_start_cronjob');
		@wp_clear_scheduled_hook('pressexpress_backupnow_download');
		@wp_clear_scheduled_hook('pressexpress_backupnow_download_ajax');
		@wp_clear_scheduled_hook('pressexpress_backupnow_save');
		@wp_clear_scheduled_hook('pressexpress_backupnow_save_ajax');


		if( $redirect ) {
			$pressexpress->redirect(array('controller'=>'principal', 'function'=>'host_info'));
		}
	}
	

}
?>
