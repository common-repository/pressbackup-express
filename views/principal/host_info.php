<?
	global $pressbackup;
	$pressbackup->import('misc.php');
	$misc = new PressExpressMisc();
	date_default_timezone_set($misc->timezone_string());
?>

<div class="updated fade" style="width:700px;" >

	<div style="float:left; width: 325px">
		<br/>==WP Info==<br/>
		* url: <?echo $info['WP']['url']."<br/>\n";?>
		* version: <?echo $info['WP']['version']."<br/>\n";?>

		<br/>==Host Info==<br/>
		* Server : <?echo $info['Host']['type']."<br/>\n";?>
		* Port : <?echo $info['Host']['port']."<br/>\n";?>
		* SAPI : <?echo $info['Host']['sapi']."<br/>\n";?>
		* MEM Max : <?echo $info['Host']['mem_max']."<br/>\n";?>
		* MEM Used : <?echo $misc->get_size_in('mb', $info['Host']['mem_used'])." MB<br/>\n";?>
		* TMP Dir: <? echo $info['Host']['tmp_dir']."<br/>\n";?>
		* TMP Free: <?echo $misc->get_size_in('mb', $info['Host']['tmp_free'])." MB<br/>\n";?>

		<br/>==Debug info==<br/>
		<p>
			* files container :<br/>
			<? for($i=0; $i < count($tmp_dir); $i++){ if(in_array($tmp_dir[$i], array('.', '..'))){continue;} echo '&nbsp;&nbsp;'.$tmp_dir[$i]; echo "<br/>\n";}?>
			<br/>
			* log container :<br/>
			<? for($i=0; $i < count($log_dir); $i++){ if(in_array($log_dir[$i], array('.', '..'))){continue;} echo '&nbsp;&nbsp;'.$log_dir[$i]; echo "<br/>\n";}?>
		</p>

		<p>
			* Log :<br/>
			<?echo $error_log;?>
		</p>

		<p>
			now: <?echo  date('< d M y - H:i:s >'); ?><br/>
			cron: <?echo  date('< d M y - H:i:s >', wp_next_scheduled('pressexpress_backup_start_cronjob') ); ?><br/>
			down: <?echo  date('< d M y - H:i:s >', wp_next_scheduled('pressexpress_backupnow_download') ); ?><br/>
			downA: <?echo  date('< d M y - H:i:s >', wp_next_scheduled('pressexpress_backupnow_download_ajax') ); ?><br/>
			save: <?echo  date('< d M y - H:i:s >', wp_next_scheduled('pressexpress_backupnow_save') ); ?><br/>
			saveA: <?echo  date('< d M y - H:i:s >', wp_next_scheduled('pressexpress_backupnow_save_ajax') ); ?><br/>
		</p>

	</div>

	<div style="float:left; width: 325px; margin-left: 50px;">
		<br/>==Browser==<br/>
		* version: <?echo $info['User']['browser']."<br/>\n";?>

		<br/>==Plugin info==<br/>
		* version: <? echo $info['Plugin']['version']; echo "<br/>\n";?>
		* Service: <? echo $info['Plugin']['service']; echo "<br/>\n";?>

		<br/>=Host modules=<br/>
		<?for ($i=0; $i< count($info['Host']['modules']); $i++) { echo '&nbsp;&nbsp;  * '.$info['Host']['modules'][$i]; if ($i % 4 == 0) {echo "<br/>\n";} } echo "<br/>\n";?>
	</div>

	<div style="clear:both;"></div>
</div>
<?echo $this->html->link(__('Clear TMP folders','pressbackup'), array('menu_type'=>'tools', 'controller'=>'principal', 'function'=>'clean', 1), array('class'=>'button'));?>
<?echo $this->html->link(__('remove scheduled jobs','pressbackup'), array('menu_type'=>'tools', 'controller'=>'principal', 'function'=>'remove_scheduledjobs', 1), array('class'=>'button'));?>
<?echo $this->html->link(__('Back to dashboard','pressbackup'), array('menu_type'=>'tools', 'controller'=>'principal', 'function'=>'dashboard'), array('class'=>'button'));?>
