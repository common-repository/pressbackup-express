<?php echo $this->html->css("default.css");?>
<?php echo $this->html->css("pb.css");?>

	<div class="tab tabactive" ><?php _e('Welcome','pressbackup'); ?></div>
	<div class="tabclear"  >&nbsp;</div>
	<div class="tab_subnav"> </div>
	<div class="tab_content">

		<?if($error){?>
			<div class="msgbox warning">
				<p><b><?php _e('Important!','pressbackup'); ?></b></p>
				<?foreach($error as $key => $value){?>
				<p>* <?echo $value;?></p>
				<?}?>
			</div>
		<?}?>


		<form method="post" action="<?php echo $this->path->router(array('controller'=>'settings', 'function'=>'config_step1_save'));?>">
			<h3><?php _e('Welcome and Thanks for installing PressBackup Express!','pressbackup'); ?></h3>
			<p><?php _e('Pressbackup allows you to schedule backups of your entire site, restore backups, and migrate your site in the event of your server failure or moving.','pressbackup'); ?><br/>
			<p><?php _e('Now press button start setup and enjoy your backups.','pressbackup'); ?></p>

			<br/><hr class="separator"/>

			<?if(isset($load_check_wizard_cron)) { ?>
				<div id="pressexpress_mess_wait">Wait a moment while we finish with the checks.<br><?php echo $this->html->img('indicator.gif'); ?> </div>
				<?php echo $this->html->js("wizzard_cron_chk.js");?>
			<?}?>

			<input id='pressexpress_bt_start' class="button" <?if(isset($load_check_wizard_cron)) { ?>style="display: none;"<?}?> name="button" type="submit" value="<?php _e('Start Setup','pressbackup'); ?>" <?if($disable_backup_files){?>disabled<?}?>>
		</form>
	</div><br/>

	<div class="tab_content">
		<h4><?php _e('Signup for PressBackup News to get updates','pressbackup'); ?></h4>
		<form action="http://infinimedia.createsend.com/t/y/s/otyxj/" method="post" id="subForm">
			<div>
				<input type="text" name="cm-otyxj-otyxj" id="otyxj-otyxj" class="longinput" value="<?php _e('Your email address here','pressbackup'); ?>" onfocus="if(jQuery(this).val() == '<?php _e('Your email address here','pressbackup'); ?>'){ jQuery(this).val('') }" onblur="if(jQuery(this).val() == ''){ jQuery(this).val('<?php _e('Your email address here','pressbackup'); ?>') }" />&nbsp;&nbsp;
				<input class="button" type="submit" value="<?php _e('Subscribe','pressbackup'); ?>" />
			</div>
		</form>
	</div>
	
	<script type="text/javascript">
	var reload_url_fail = '<? echo str_replace('&amp;', '&', $this->path->router(array('controller'=>'settings', 'function'=> 'wizard_cron_fail'))); ?>';
	</script>
