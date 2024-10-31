<?php

/*
	WordPress Framework, activation  v0.1
	developer: Perecedero (Ivan Lansky) perecedero@gmail.com
*/


	function on_activation ()
	{
		global $pressexpress;
		//options
		$preferences['pressexpress']['configured']=0;

		$preferences['pressexpress']['backup']['time']=24;
		$preferences['pressexpress']['backup']['copies']=5;
		$preferences['pressexpress']['backup']['type']='0';

		$preferences['pressexpress']['pressbackuppro']['credential']='';

		$preferences['pressexpress']['compatibility']['background']=10;
		$preferences['pressexpress']['compatibility']['zip'] = 10;

		update_option('pressexpress_preferences',$preferences);
		update_option('pressexpress_wizard_cron_state', 'not tested');
		update_option('pressexpress_first_run', true);


		$pressexpress->import('Pro.php');
		$pb = new PressExpress();
		$pb->wasInstall();
	}

	function on_deactivation ()
	{
		global $pressexpress;
		$pressexpress->import('backup_functions.php');

		$pb = new PressExpressBackup();
		$pb->remove_schedule();

		delete_option('pressexpress_preferences');
		delete_option('pressexpress_check_cron');
	}


?>
