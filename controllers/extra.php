<?
 /**
 * Extra Controller for Pressbackup Express.
 *
 * This Class provide misc function that are called automaticaly at some point
 *
 * Licensed under The GPL v2 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @link				http://pressbackup.com
 * @package		controlers
 * @subpackage	controlers.extra
 * @since			0.5
 * @license			GPL v2 License
 */

class PressExpressExtra {

	/**
	 * Check if the scheluded taks is active and active otherwise
	 *
	 * Note: this function its called at the init of the plugin
	*/
	function checks () {
		//tools
		global $pressexpress;
		$preferences= get_option('pressexpress_preferences');

		$pressexpress->import('misc.php');
		$misc = new PressExpressMisc();

		//set the type of service use to get/show backups
		$service = $misc->current_service();

		//set the type of credential to see what option show in this page
		$credentials = false; if ($service) { $credentials = true; }

		//fix to missing schedule
		if ($credentials && !$ns=wp_next_scheduled('pressexpress_backup_start_cronjob')){
			$pressexpress->import('backup_functions.php');
			$pb = new PressExpressBackup();
			$pb->add_schedule();
		}

		//check for new preferences
		$need_update = false;
		if (!isset($preferences['pressexpress']['compatibility']['background'])){
			$preferences['pressexpress']['compatibility']['background']=10;
			$need_update = true;
		}
		if (!isset($preferences['pressexpress']['compatibility']['zip'])){
			$preferences['pressexpress']['compatibility']['zip'] = 10;
			$need_update = true;
		}
		if (!isset($preferences['pressexpress']['local']['path'])){
			$preferences['pressexpress']['local']['path'] = '';
			$need_update = true;
		}
		if (!isset($preferences['pressexpress']['dropbox']['credential'])){
			$preferences['pressexpress']['dropbox']['credential'] = '';
			$need_update = true;
		}
		if (!isset($preferences['pressexpress']['s3']['bucket_name'])){
			$preferences['pressexpress']['s3']['bucket_name'] = '';
			$need_update = true;
		}

		if($need_update) {
			update_option('pressexpress_preferences',$preferences);
		}
		
	}

	/**
	 * Restore the permalinks
	 *
	 * called after a restore is done
	 * Note: this function its called at the admin init
	 */
	function restore_htaccess () {
		//tools
		global $pressexpress;
		global $wp_rewrite;
		$preferences= get_option('pressexpress_preferences');

		if(isset($preferences['pressexpress']['restore']))
		{
			$wp_rewrite->flush_rules();
			unset($preferences['pressexpress']['restore']);
			update_option('pressexpress_preferences',$preferences);
		}
		return true;
	}

}
?>
