<?php

 /**
 * Backup Functions lib for Pressbackup.
 *
 * This Class provide the functionality to mannage backups
 * functions for creation, restoring, get and save from S3 and Ppro
 *
 * Licensed under The GPL v2 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @link				http://pressbackup.com
 * @package		libs
 * @subpackage	libs.backups
 * @since			0.1
 * @license			GPL v2 License
 */

	class PressExpressBackup {

	//		Create backup functions
	//----------------------------------------------------------------------------------------

		function create ()
		{
			//set infinite time for this action
			set_time_limit (0);

			//tools and info
			global $pressexpress;
			$pressexpress->import('misc.php');
			$misc = new PressExpressMisc();

			$preferences=get_option('pressexpress_preferences');

			//clean log && tmp dir
			$misc->perpare_folder($pressexpress->Path->Dir['LOGTMP']);
			$misc->perpare_folder($pressexpress->Path->Dir['PBKTMP']);

			@file_put_contents($pressexpress->Path->Dir['LOGTMP'] . DS . 'create.log', 'start');

			//zip files and export db
			if(!$this->backup_files() || !$this->backup_db())
			{
				return false;
			}

			//file name of backup
			$backup_file_type = str_replace(',', '-', $preferences['pressexpress']['backup']['type']);
			$name=str_replace(' ', '-', $misc->normalize_string(strtolower(get_bloginfo( 'name' ))));
			$zip_file = $pressexpress->Path->Dir['PBKTMP'] . DS . uniqid($name.'-backup_'.$backup_file_type.'_').'.zip';
			$folder = str_replace($pressexpress->Path->Dir['SYSTMP'] . DS, '', $pressexpress->Path->Dir['PBKTMP']);
			$folder = $folder.DS;

			//zip files
			$type = 'shell';
			if( $preferences['pressexpress']['compatibility']['zip'] == 10 ) {
				$type = 'php';
			}
			$res = $misc->zip($type, array('context_dir'=>$pressexpress->Path->Dir['SYSTMP'], 'dir' => $folder, 'zip' => $zip_file, 'compression' => 0));

			//check response of zip creation
			if( !$res ) {
				$pressexpress->Session->write( 'error_msg', __("Zip file is corrupt - creation failed",'pressbackup'));
				@file_put_contents($pressexpress->Path->Dir['LOGTMP'] . DS . 'create.log', 'fail');
				return false;
			}

			@file_put_contents($pressexpress->Path->Dir['LOGTMP'] . DS . 'create.log', 'finish');
			return $zip_file;
		}

		//shortcut
		function save_on ($type, $zip_file){
			//set_time_limit (0);

			if ($type == 'Pro'){

				return $this->save_on_pro($zip_file);
			}
		}
		
		function save_on_pro($zip_file)
		{
			@set_time_limit (0);

			//tools and info
			global $pressexpress;
			$preferences=get_option('pressexpress_preferences');
			$pressexpress->import('Pro.php');
			$pressexpress->import('misc.php');
			$misc = new PressExpressMisc();

			@file_put_contents($pressexpress->Path->Dir['LOGTMP'] . DS . 'sent.log', 'start');

			$pressexpress->Session->delete( 'general_msg');

			//create Pro interface
			$credentials=explode('|AllYouNeedIsLove|', base64_decode($preferences['pressexpress']['pressbackuppro']['credential']));
			$pro = new PressExpress($credentials[0], $credentials[1]);

			//check site
			if(!$pro->check()){
				$pressexpress->Session->write( 'error_msg',  __("This blog is not registered on your Pressbackup Pro account",'pressbackup'));
					@file_put_contents($pressexpress->Path->Dir['LOGTMP'] . DS . 'sent.log', 'fail');
				return false;
			}

			if(!$pro->putFile($zip_file)) {
				//save massage
				$pressexpress->Session->write( 'error_msg',  __("Connection with Pressbackup Pro fail. Try again later",'pressbackup'));
					@file_put_contents($pressexpress->Path->Dir['LOGTMP'] . DS . 'sent.log', 'fail');
				return false;
			}

			//check the number of backup stored
			$bucket_files = $pro->getFilesList();
			$bucket_files=@$misc->msort('pressbackup', $bucket_files);
			$bucket_files=$misc->filter_files('this_site', $bucket_files);
			if($preferences['pressexpress']['backup']['copies'] != 7 && count($bucket_files) > $preferences['pressexpress']['backup']['copies'])
			{
				$this->delete($bucket_files[count($bucket_files) -1]['name'], false);
			}

			@file_put_contents($pressexpress->Path->Dir['LOGTMP'] . DS . 'sent.log', 'finish');
			return true;
		}

		function backup_files()
		{
			//tools and info
			global $pressexpress;
			$pressexpress->import('misc.php');
			$preferences=get_option('pressexpress_preferences');
			$misc = new PressExpressMisc();


			//read backup preferences
			$backup_file_type = explode(',', $preferences['pressexpress']['backup']['type']);

			//uploads
			if(in_array('1', $backup_file_type) && file_exists(WP_CONTENT_DIR. DS.'uploads'))
			{

				@file_put_contents($pressexpress->Path->Dir['LOGTMP'] . DS . 'create.log', __('Creating Uploads folder backup','pressbackup'));

				$zip_file = $pressexpress->Path->Dir['PBKTMP'].DS.'uploads.zip';
				$folder = 'uploads'.DS;

				//zip files
				$type = 'shell';
				if( $preferences['pressexpress']['compatibility']['zip'] == 10 ) {
					$type = 'php';
				}
				$res = $misc->zip($type, array('context_dir'=>WP_CONTENT_DIR, 'dir' => $folder, 'zip' => $zip_file));


				//check response of zip creation
				if( !$res ) {
					//$pressexpress->Session->write( 'error_msg',  __("Can not create zip archive for uploads folder!",'pressbackup'));
					@file_put_contents($pressexpress->Path->Dir['LOGTMP'] . DS . 'create.log', 'fail');
					return false;
				}
			}

			//plugins
			if(in_array('3', $backup_file_type))
			{
				@file_put_contents($pressexpress->Path->Dir['LOGTMP'] . DS . 'create.log', __('Creating Plugins folder backup','pressbackup'));

				$zip_file = $pressexpress->Path->Dir['PBKTMP'].DS.'plugins.zip';
				$folder = 'plugins'.DS;

				//zip files
				$type = 'shell';
				if( $preferences['pressexpress']['compatibility']['zip'] == 10 ) {
					$type = 'php';
				}
				$res = $misc->zip($type, array('context_dir'=>WP_CONTENT_DIR, 'dir' => $folder, 'zip' => $zip_file));

				//check response of zip creation
				if( !$res ) {
					//$pressexpress->Session->write( 'error_msg',  __("Can not create zip archive for plugins folder!",'pressbackup'));
					@file_put_contents($pressexpress->Path->Dir['LOGTMP'] . DS . 'create.log', 'fail');
					return false;
				}
			}

			//themes
			if(in_array('5', $backup_file_type))
			{
				@file_put_contents($pressexpress->Path->Dir['LOGTMP'] . DS . 'create.log', __('Creating Themes folder backup','pressbackup'));

				$zip_file = $pressexpress->Path->Dir['PBKTMP'].DS.'themes.zip';
				$folder = 'themes'.DS;

				//zip files
				$type = 'shell';
				if( $preferences['pressexpress']['compatibility']['zip'] == 10 ) {
					$type = 'php';
				}
				$res = $misc->zip($type, array('context_dir'=>WP_CONTENT_DIR, 'dir' => $folder, 'zip' => $zip_file));

				//check response of zip creation
				if( !$res ) {
					//$pressexpress->Session->write( 'error_msg',  __("Can not create zip archive for themes folder!",'pressbackup'));
					@file_put_contents($pressexpress->Path->Dir['LOGTMP'] . DS . 'create.log', 'fail');
					return false;
				}
			}
			return true;
		}

		function backup_db ()
		{
			//tools and info
			global $wpdb;
			global $pressexpress;
			$preferences=get_option('pressexpress_preferences');

			//maximun multimple inserts
			$insert_max = 50;

			//read backup preferences
			$backup_db_type = explode(',', $preferences['pressexpress']['backup']['type']);

			if(in_array('7', $backup_db_type))
			{

				@file_put_contents($pressexpress->Path->Dir['LOGTMP'] . DS . 'create.log', __('Creating Database backup','pressbackup'));

				//save .httaccess for this server
				try { @copy(ABSPATH.'.htaccess', $pressexpress->Path->Dir['PBKTMP'].DS.'.htaccess'); } catch (Exception $e) { }

				//save server for this SQL
				$file =$pressexpress->Path->Dir['PBKTMP'].DS.'server';
				if(!$fh=fopen( $file, 'w'))
				{
					$pressexpress->Session->write( 'error_msg',  __("can not create file to store server for this SQL dump",'pressbackup'));
					@file_put_contents($pressexpress->Path->Dir['LOGTMP'] . DS . 'create.log', 'fail');
					return false;
				}
				fwrite($fh, get_bloginfo( 'wpurl' ));
				fclose($fh);

				//create .sql file
				$file = $pressexpress->Path->Dir['PBKTMP'].DS.'database.sql';
				if(!$fh=fopen( $file, 'w'))
				{
					$pressexpress->Session->write( 'error_msg',  __("can not create file for SQL dump",'pressbackup'));
					@file_put_contents($pressexpress->Path->Dir['LOGTMP'] . DS . 'create.log', 'fail');
					return false;
				}

				//dump DB
				$file_header= '-- PressBackup SQL Dump'."\n".
				'-- version 1.0'."\n".
				'-- http://www.infinimedia.com'."\n\n".
				'SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";'."\n\n";
				fwrite($fh, $file_header."\n\n");

				$DB_tables=$wpdb->get_results('SHOW TABLES');
				$method2 = 'Tables_in_'.DB_NAME;

				for($i=0; $i<count($DB_tables); $i++)
				{

					//table estructure
					$query = $wpdb->get_results('show create table '.$DB_tables[$i]->$method2);

					//"Create Table" with a space in the middle.
					$method = 'Create Table';
					$table_structure = $query[0]->$method;
					$table_structure = str_replace ('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $table_structure);
					fwrite($fh, "\n\n".$table_structure.";\n\n");

					//inserts -header
					$describe_table = $wpdb->get_results('DESCRIBE '.$DB_tables[$i]->$method2);
					$insert_header=$fields=array();
					for ($j=0; $j< count($describe_table); $j++)
					{
						$insert_header[$j] = '`'. $describe_table[$j]->Field.'`';
						$fields[$j]=$describe_table[$j]->Field;
					}
					$insert_header= 'INSERT INTO `'.$DB_tables[$i]->$method2.'` ( '. join(',', $insert_header). ') VALUES ';


					//count rows for inserts -data
					$inserts_count = $wpdb->get_results('SELECT count(*) as cant FROM  `'.$DB_tables[$i]->$method2.'`');
					$insert_pages = ceil ($inserts_count[0]->cant / $insert_max);

					//dump insert rows
					for ($l = 0; $l < $insert_pages; $l ++)
					{
						//inserts -data
						unset($inserts); $inserts = array();
						$inserts = $wpdb->get_results('SELECT * FROM  `'.$DB_tables[$i]->$method2.'` LIMIT '.($l * $insert_max).','.$insert_max);

						unset($insert_data); $insert_data=array();
						for ($j=0; $j< count($inserts); $j++)
						{
							unset($popo); $popo=array();
							for ($k=0; $k< count($fields); $k++)
							{
								$popo[$k]='\''.mysql_real_escape_string($inserts[$j]->$fields[$k]).'\'';
							}
							$insert_data[$j] = '('.join(',', $popo).')';
						}
						if($insert_data) {
							fwrite($fh, "\n".$insert_header."\n".join(",\n", $insert_data).';'."\n\n");
						}
					}
				}
				fclose($fh);
			}

			return true;
		}

	//		Restore backup functions
	//----------------------------------------------------------------------------------------

		function restore ($args)
		{
			//set infinite time for this action
			set_time_limit (0);

			//tools and info
			global $pressexpress;
			$pressexpress->import('misc.php');
			$misc = new PressExpressMisc();

			//clean log && tmp dir
			$misc->perpare_folder($pressexpress->Path->Dir['LOGTMP']);
			$misc->perpare_folder($pressexpress->Path->Dir['PBKTMP']);

			//extract backup
			$zip = new ZipArchive();
			if(!$zip->open($args['tmp_name'])===true)
			{
				$pressexpress->Session->write( 'error_msg',  __("Backup seems corrupt. Process aborted",'pressbackup'));
				return false;
			}

			$old_name = $pressexpress->Path->Dir['SYSTMP']. DS . dirname ($zip->getNameIndex(0));

			$zip->extractTo($pressexpress->Path->Dir['SYSTMP'] );
			$zip->close();

			$new_name = $pressexpress->Path->Dir['PBKTMP'];

			@rmdir ($pressexpress->Path->Dir['PBKTMP']);
			@rename ( $old_name , $new_name );

			//restore backup
			if(!$this->restore_files() || !$this->restore_db())
			{
				return false;
			}

			$pressexpress->Session->write( 'general_msg',  __("System restored!",'pressbackup'));
			return true;
		}

		function restore_files()
		{
			//tools and info
			global $pressexpress;
			$pressexpress->import('misc.php');
			$misc = new PressExpressMisc();

			//shortcuts
			$PBKTMP = $pressexpress->Path->Dir['PBKTMP'].DS;

			$zip = new ZipArchive();

			//restore themes
			if(file_exists($PBKTMP .'themes.zip')){
				if($zip->open( $PBKTMP .'themes.zip') !== true) {
					$pressexpress->Session->write( 'error_msg',  __("Can not restore themes, themes backup is corrupt. Restore process aborted",'pressbackup'));
					return false;
				}

				if(!@$zip->extractTo(WP_CONTENT_DIR))
				{
					$zip->close();
					$pressexpress->Session->write( 'error_msg',  __("Can not restore themes folder, permission denied to write on <b>wp-content</b> foder. Restore process aborted",'pressbackup'));
					return false;
				}
				$zip->close();
			}

			//restore uploads
			if(file_exists($PBKTMP .'uploads.zip')){
				if($zip->open( $PBKTMP .'uploads.zip') !== true)
				{
					$pressexpress->Session->write( 'error_msg',  __("Can not restore uploads folder, uploads backup is corrupt. Restore process aborted",'pressbackup'));
					return false;
				}

				if(!@$zip->extractTo(WP_CONTENT_DIR))
				{
					$zip->close();
					$pressexpress->Session->write( 'error_msg',  __("Can not restore uploads folder, permission denied to write on <b>wp-content</b> foder. Restore process aborted",'pressbackup'));
					return false;
				}

				$zip->close();
			}

			//restore plugins
			if(file_exists($PBKTMP .'plugins.zip')){
				if($zip->open( $PBKTMP .'plugins.zip') !== true) {
					$pressexpress->Session->write( 'error_msg',  __("Can not restore plugins folder, plugins backup is corrupt. Restore process aborted",'pressbackup'));
					return false;
				}

				if(!@$zip->extractTo(WP_CONTENT_DIR))
				{
					$zip->close();
					$pressexpress->Session->write( 'error_msg',  __("Can not restore plugins folder, permission denied to write on <b>wp-content</b> foder. Restore process aborted",'pressbackup'));
					return false;
				}
				$zip->close();
			}

			$misc->actionFolder(WP_CONTENT_DIR , array('function' => 'chmod', 'param' => array(0755)));

			return true;
		}

		function restore_db()
		{
			require_once(ABSPATH . '/wp-admin/admin.php');
			require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );

			//tools and info
			global $pressexpress;
			global $wpdb;
			global $wp_rewrite;

			//shortcuts
			$PBKTMP = $pressexpress->Path->Dir['PBKTMP'].DS;

			//check if need to restore DB
			if(!file_exists($PBKTMP .'database.sql')){ return true; }

			//get old server name to can restore new DB with it
			if(!$fn=fopen( $PBKTMP .'server', 'rb')) {
				$pressexpress->Session->write( 'error_msg',  __("Can not restore database, missing files. Restore process aborted",'pressbackup'));
				return false;
			}
			$last_server=trim(fgets($fn)); fclose($fn);
			$new_server = get_bloginfo( 'wpurl' );

			//get SQL dump
			if(!$DBdump = @fopen($PBKTMP .'database.sql', 'rb')) {
				$pressexpress->Session->write( 'error_msg',  __("Can not restore database, missing files (.sql). Restore process aborted",'pressbackup'));
				return false;
			}

			//Drop current DB tables
			$DB_tables=$wpdb->get_results('SHOW TABLES');
			$method = 'Tables_in_'.DB_NAME;
			for($i=0; $i<count($DB_tables); $i++)
			{
				$wpdb->query('DROP TABLE '.$DB_tables[$i]->$method);
			}

			//Read headers from .sql
			 $inserts='';
			while (($buffer=fgets($DBdump)) && !preg_match("/^INSERT INTO(.)*/", $buffer) && !preg_match("/^CREATE TABLE IF NOT EXISTS(.)*/", $buffer))
			{
				$inserts .= $buffer;
			}
			$inserts = str_replace($last_server, $new_server, $inserts); //echo $inserts;
			@$wpdb->query(trim($inserts));

			//Read until EOF cuting sql into create and insert stataments
			while($buffer)
			{
				if(preg_match("/^CREATE TABLE IF NOT EXISTS(.)*/", $buffer)){

					$inserts = $buffer;
					while(($buffer=fgets($DBdump)) && !preg_match("/^INSERT INTO(.)*/", $buffer) && !preg_match("/^CREATE TABLE IF NOT EXISTS(.)*/", $buffer))
					{
						$inserts .= $buffer;
					}
					$inserts = str_replace($last_server, $new_server, $inserts); //echo $inserts;
					dbDelta( trim($inserts) ); //@$wpdb->query(trim($inserts));
				}

				if(preg_match("/^INSERT INTO(.)*/", $buffer)) {

					//save insert header
					$header = $buffer;

					// start read insert values
					$i=0; $inserts='';
					while(($buffer=fgets($DBdump)) && !preg_match("/^INSERT INTO(.)*/", $buffer) && !preg_match("/^CREATE TABLE IF NOT EXISTS(.)*/", $buffer))
					{
						$inserts .= $buffer; $i++;

						//inser 50 results at once
						if($i==50) {
							$inserts= trim($inserts);
							if( substr($inserts, -1) == ',') { $inserts = substr_replace($inserts, ';', -1, 1);}
							$inserts = str_replace($last_server, $new_server, $inserts);
							@$wpdb->query(trim($header.$inserts)); //echo $header.$inserts;
							$i=0; $inserts='';
						}
					}

					//if something remaing to save
					if($i>0) {
						$inserts= trim($inserts);
						if( substr($inserts, -1) == ',') { $inserts = substr_replace($inserts, ';', -1, 1);}
						$inserts = str_replace($last_server, $new_server, $inserts);
						@$wpdb->query(trim($header.$inserts)); //echo trim($header.$inserts)."\n\n";

					}
				}

			}
			fclose($DBdump);

			//update options
			$siteopts = wp_load_alloptions();
			$this->update_site_options($siteopts, $last_server, $new_server);

			//copy backed up .htaccess
			try { @copy($PBKTMP .'.htaccess', ABSPATH .'.htaccess') ; } catch (Exception $e) {} 

			//remake .htaccess
			$preferences= get_option('pressexpress_preferences');
			$preferences['pressexpress']['restore']=true;
			update_option('pressexpress_preferences',$preferences);

			return true;
		}

		function update_site_options(array $options, $old_url, $new_url) {
			require_once ABSPATH .'wp-includes/functions.php';
			foreach ($options as $option_name => $option_value) {

				if (FALSE === strpos($option_value, $old_url)) {
					continue;
				}

				if (is_array($option_value)) {
					$this->update_site_options($option_value, $old_url, $new_url);
				}

				// attempt to unserialize option_value
				if(!is_serialized($option_value)) {
					$newvalue = str_replace($old_url, $new_url, $option_value);
				} else {
					$newvalue = $this->update_serialized_options(maybe_unserialize($option_value), $old_url, $new_url);
				}

				update_option($option_name, $newvalue);
			}
		}

		function update_serialized_options($data, $old_url, $new_url) {
			require_once ABSPATH .'wp-includes/functions.php';
			// ignore _site_transient_update_*
			if(is_object($data)){
				return $data;
			}

			foreach ($data as $key => $val) {
				if (is_array($val)) {
						$data[$key] = $this->update_serialized_options($val, $old_url, $new_url);
				} else {
					if (!strstr($val, $old_url)) {
						continue;
					}
					$data[$key] = str_replace($old_url, $new_url, $val);
				}
			}
			return $data;
		}

	//		Get backup functions
	//----------------------------------------------------------------------------------------

		function get ($file='')
		{
			//set infinite time for this action
			set_time_limit (0);

			//tools and info
			global $pressexpress;

			if(!$file){
				$pressexpress->Session->write( 'error_msg',  __("Missing backup file name",'pressbackup'));
				return false;
			}

			$pressexpress->import('misc.php');
			$misc = new PressExpressMisc();

			//get the type of service use to get/show backups
			$service = $misc->current_service();

			//where to put backup file
			$stored_in = $store_in = $pressexpress->Path->Dir['SYSTMP']. DS . uniqid();

			// succesfully transfer ?
			$transfer = false;
			

			//retrive backups from Pro
			if( $service['id'] == 'Pro' )
			{
				$pressexpress->import('Pro.php');
				$credentials = explode('|AllYouNeedIsLove|', base64_decode( $service['credentials'] ));
				$pbp = new PressExpress($credentials[0], $credentials[1]);
				$transfer = $pbp->getFile($file, $store_in);
			}

			if($transfer)
			{
				return $stored_in;
			}

			$pressexpress->Session->write( 'error_msg',  __('Failed to get file','pressbackup'));
			return false;
		}

		/* Only for pressbackup pro*/
		function get2 ($file='')
		{
			//set infinite time for this action
			set_time_limit (0);

			//tools and info
			global $pressexpress;
			$preferences=get_option('pressexpress_preferences');

			if(!$file){
				$pressexpress->Session->write( 'error_msg',  __("Missing backup file name",'pressbackup'));
				return false;
			}

			//get the type of service use to get/show backups
			$pressexpress->import('misc.php');
			$misc = new PressExpressMisc();
			$service = $misc->current_service();

			// succesfully transfer ?
			$transfer = false;

			if( $service['id'] == 'Pro' )
			{
				$pressexpress->import('Pro.php');
				$credentials = explode('|AllYouNeedIsLove|', base64_decode( $service['credentials'] ));
				$pbp = new PressExpress($credentials[0], $credentials[1]);
				$transfer=$pbp->getFile2($file);
			}

			if($transfer !== false) {
				return true;
			} 
			
			$pressexpress->Session->write( 'error_msg',  __('Failed to get file 2','pressbackup'));
			return false;
		}

	//		Delete backup functions
	//----------------------------------------------------------------------------------------

		function delete($file='', $inform=true)
		{
			//set infinite time for this action
			set_time_limit (0);

			//tools and info
			global $pressexpress;

			if(!$file){
				if($inform){ $pressexpress->Session->write( 'error_msg',  __("Missing backup file name",'pressbackup')); }
				return false;
			}

			$pressexpress->import('misc.php');
			$misc = new PressExpressMisc();

			//get the type of service use to get/show backups
			$service = $misc->current_service();


			// deleted ?
			$deleted = false;

			//retrive backups from Pro
			if( $service['id'] == 'Pro' )
			{
				$pressexpress->import('Pro.php');
				$credentials = explode('|AllYouNeedIsLove|', base64_decode( $service['credentials'] ));
				$pbp = new PressExpress($credentials[0], $credentials[1]);
				$deleted = $pbp->deleteFile($file);
			}

			if($deleted)
			{
				if($inform){ $pressexpress->Session->write( 'general_msg',  __('Backup deleted!','pressbackup')); }
				return true;
			}

			return false;

		}

	//		Schedule functions
	//----------------------------------------------------------------------------------------

		function add_schedule($time=null, $task = 'pressexpress_backup_start_cronjob') {
			global $pressexpress;
			$preferences=get_option('pressexpress_preferences');

			$pressexpress->import('misc.php');
			$misc = new PressExpressMisc();

			date_default_timezone_set($misc->timezone_string());

			if($time){
				$start_time = strtotime($time);
			}
			else{
				$start_time = strtotime('now') + ($preferences['pressexpress']['backup']['time'] * (60 * 60));
			}
			$this->remove_schedule($task);
			
			@wp_schedule_single_event($start_time, $task);
			return true;
		}

		function remove_schedule($task = 'pressexpress_backup_start_cronjob') {
			@wp_clear_scheduled_hook($task);
			return true;
		}

	function find_something_to_do($scheduler){
		if(!$scheduler['activated']){return false;}

		$jobs_to_do = '';

		if($this->is_scheduled($scheduler['db']['time'],$scheduler['db']['last_date'])){
			if($jobs_to_do != ''){$jobs_to_do .= ',';}
			$jobs_to_do .= '7';
		}
		if($this->is_scheduled($scheduler['themes']['time'],$scheduler['themes']['last_date'])){
			if($jobs_to_do != ''){$jobs_to_do .= ',';}
			$jobs_to_do .= '5';
		}
		if($this->is_scheduled($scheduler['plugins']['time'],$scheduler['plugins']['last_date'])){
			if($jobs_to_do != ''){$jobs_to_do .= ',';}
			$jobs_to_do .= '3';
		}
		if($this->is_scheduled($scheduler['uploads']['time'],$scheduler['uploads']['last_date'])){
			if($jobs_to_do != ''){$jobs_to_do .= ',';}
			$jobs_to_do .= '1';
		}

		return $jobs_to_do;
	}

	function is_scheduled($rate,$last_date){
		if($rate == '0'){ return false;}

		$curdate = strtotime('now');

		//calculates the time elapsed since the last execution so far
		$curdate_aux = intval($curdate);
		$last_date = intval($last_date);
		$elapsed_time = intval(($curdate_aux - $last_date)/3600);
		if($elapsed_time >= $rate){
			//must be executed
			return true;
		}
		//must not be executed
		return false;
	}

	function was_scheduled_for($rate,$last_date){
		//pass the rate to seconds
		$rate = $rate * 3600;

		//calculate the execution date scheduled
		$scheduled_date = $last_date + $rate;

		//pass to valid date format
		//$scheduled_date = date('Y-m-d H:i:s', $scheduled_date);

		return $scheduled_date;
	}

	function update_last_date($jobs_to_do){

		$preferences= get_option('pressexpress_preferences');

		if($jobs_to_do == ''){
			exit;
		}

		$jobs_to_do = explode(',',$jobs_to_do);
	
		if(in_array('7',$jobs_to_do)){
			$preferences['pressexpress']['backup_advanced']['db']['last_date'] =  $this->was_scheduled_for($preferences['pressexpress']['backup_advanced']['db']['time'],$preferences['pressexpress']['backup_advanced']['db']['last_date']);
		}
		if(in_array('5',$jobs_to_do)){
			$preferences['pressexpress']['backup_advanced']['themes']['last_date'] = $this->was_scheduled_for($preferences['pressexpress']['backup_advanced']['themes']['time'],$preferences['pressexpress']['backup_advanced']['themes']['last_date']);
		}
		if(in_array('3',$jobs_to_do)){
			$preferences['pressexpress']['backup_advanced']['plugins']['last_date'] = $this->was_scheduled_for($preferences['pressexpress']['backup_advanced']['plugins']['time'],$preferences['pressexpress']['backup_advanced']['plugins']['last_date']);
		}
		if(in_array('1',$jobs_to_do)){
			$preferences['pressexpress']['backup_advanced']['uploads']['last_date'] = $this->was_scheduled_for($preferences['pressexpress']['backup_advanced']['uploads']['time'],$preferences['pressexpress']['backup_advanced']['uploads']['last_date']);
		}
		update_option('pressexpress_preferences', $preferences); 
	}

	function min_time($types = null){
		if(!$types){
			$preferences= get_option('pressexpress_preferences');
			$types = $preferences['pressexpress']['backup_advanced'];
		}
		if(isset($types['activated'])){
			unset($types['activated']);
		}
		$min['time'] = 99999999;
		foreach($types as $type=>$settings){
			if($settings['time'] && $settings['time'] < $min['time'] ){ $min = $settings; }//return the time and last_date
		}

		if(!isset($min['last_date'])){$min['last_date'] = strtotime('now');}

		return ($min['time'] == 99999999)?false:$min;
	}

	function next_scheduled_jobs($types=null){

		if(!$types){
			$preferences= get_option('pressexpress_preferences');
			$types = $preferences['pressexpress']['backup_advanced'];
		}
		if(isset($types['activated'])){
			unset($types['activated']);
		}

		$next_dates;
		$next_date = '';
		foreach($types as $key=>$type){
			if($type['time'] == 0){ continue; }

			$next_dates[$key] = $this->was_scheduled_for($type['time'],$type['last_date']);
			if($next_execution == ''){
				$next_execution = $next_dates[$key];
			}else if($next_execution > $next_dates[$key]){
				$next_execution = $next_dates[$key];
			}
		}

		$result_type = array();
		foreach($next_dates as $key=>$date){
			if($next_execution == $date){
				$result_type[] = $key;
			}
		}

		return  str_replace( array('uploads', 'plugins', 'themes', 'db'), array('1', '3', '5', '7') , join( ',', $result_type) );
	}


	function activated_types($types=null){

		if(!$types){
			$preferences= get_option('pressexpress_preferences');
			$types = $preferences['pressexpress']['backup_advanced'];
		}

		if(isset($types['activated'])){ unset($types['activated']); }

		$activated = array();
		foreach($types as $type=>$settings){
			if($settings['time']){ $activated[] = $type; }
		}

		return  str_replace( array('uploads', 'plugins', 'themes', 'db'), array('1', '3', '5', '7') , join(',', $activated) );
	}

}
?>
