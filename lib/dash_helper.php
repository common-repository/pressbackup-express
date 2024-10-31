<?
	Class PressExpressDashHelper {

		var $types_names = null;
		var $types_numbers = null;

		function __construct() { 
			$this->types_names = array(__('Database','pressbackup'), ' '.__('Themes','pressbackup'), ' '.__('Plugins','pressbackup'), ' '.__('Uploads','pressbackup'));
			$this->types_numbers = array(7, 5, 3, 1);
		}


		function print_current_settings () {

			//tools
			global $pressexpress;
			$preferences= get_option('pressexpress_preferences');

			$pressexpress->import('backup_functions.php');
			$pb = new PressExpressBackup();

			$pressexpress->import('misc.php');
			$misc = new PressExpressMisc();

			//get the type of credential
			$service = $misc->current_service();
			$advanced_backups = $preferences['pressexpress']['backup_advanced']['activated'];

			$activated_type = str_replace( $this->types_numbers, $this->types_names, $preferences['pressexpress']['backup']['type'] );
			$next_type = null;
			$schedule = __('Disabled','pressbackup');
			$storage_service = '--';

			if($service && !$advanced_backups) {
				$schedule = __('Next on','pressbackup').': '.date_i18n('M d, H:i', wp_next_scheduled('pressexpress_backup_start_cronjob'));
			}

			if($service && $advanced_backups) {
				$activated_type  = str_replace( $this->types_numbers, $this->types_names, $pb->activated_types() );
				$next_type  = str_replace( $this->types_numbers, $this->types_names, $pb->next_scheduled_jobs() );
				$schedule = __('Next on','pressbackup').': '.date_i18n('M d, H:i', wp_next_scheduled('pressexpress_backup_start_cronjob')).' ( '.__('Will backup','pressbackup').': '.$next_type.' )';
			}

			echo
			'
			<div class="left">
				<h3 style="margin-bottom: 0px;">'.__('Backup type', 'pressbackup').'</h3>
				<p style="margin-top: 5px;">'.$activated_type.'</p>
			</div>
			<div class="left" style="margin-left: 100px">
				<h3 style="margin-bottom: 0px;">'.__('Scheduled backups', 'pressbackup').'</h3>
				<p style="margin: 5px 0 5px;">'.$schedule.'</p>
			</div>
			<div class="clear"></div>
			';

		}



	}

?>
