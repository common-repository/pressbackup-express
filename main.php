<?php
/*
Plugin Name: PressBackup Express
Plugin URI: http://pressbackup.com
Description: PressBackup Express automatically backs up your entire WordPress site to our cloud service. Requires a PressBackup.com Express membership to use.
Author: Infinimedia Inc.
Version: 1.0.1
Author URI: http://infinimedia.com/
*/

	//init framework
	require_once('.core/w2pf_init.php');
	global $FramePress;
	global $pressexpress;
	$pressexpress = new $FramePress(__FILE__);

	$wp_pages =array (

		'tools' =>array(
			array(
				'page_title' => 'PressBackup Express',
				'menu_title' => 'PressBackup Express',
				'capability' => 'administrator',
				'menu_slug' => 'principal',
			),
		),
		'settings' =>array(
			array(
				'page_title' => 'PressBackup Express',
				'menu_title' => 'PressBackup Express Settings',
				'capability' => 'administrator',
				'menu_slug' => 'settings',
			),
		),
	);

	$wp_actions = array (
		array(
			'tag' => 'init',
			'handler' => 'extra',
			'function' => 'checks',
		),
		array(
			'tag' => 'admin_init',
			'handler' => 'extra',
			'function' => 'restore_htaccess',
		),

		//start schedule job
		array(
			'tag' => 'pressexpress_backup_start_cronjob',
			'handler' => 'principal',
			'function' => 'backup_create_and_send',
		),

		// create and send backup
		array(
			'tag' => 'pressexpress_backupnow_save',
			'handler' => 'principal',
			'function' => 'backup_create_and_send',
		),


		// create and send backup ajax
		array(
			'tag' => 'pressexpress_backupnow_save_ajax',
			'handler' => 'principal',
			'function' => 'backup_create_and_send',
			'is_ajax' => true
		),

		// check create and send status
		array(
			'tag' => 'pressexpress_check_backupnow_status',
			'handler' => 'principal',
			'function' => 'check_backupnow_status',
			'is_ajax' => true,
		),

		// wizard cron task, to test background process creation
		array(
			'tag' => 'pressexpress_wizard_cron_task',
			'handler' => 'settings',
			'function' => 'wizard_cron_task'
		),

		// wizard cron task status checker
		array(
			'tag' => 'pressexpress_wizard_cron_status',
			'handler' => 'settings',
			'function' => 'wizard_cron_status',
			'is_ajax' => true,
		),
	);


	$pressexpress->Page->add($wp_pages);
	$pressexpress->Action->add($wp_actions);
?>
