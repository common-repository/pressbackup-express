	<?php global $pressexpress; ?>
	<?php $pressexpress->import('dash_helper.php'); ?>
	<?php $dh = new PressExpressDashHelper(); ?>


	<? date_default_timezone_set($timezone_string);?>

	<?php echo $this->html->css("default.css");?>
	<?php echo $this->html->css("pb.css");?>

	<div class="tab tabactive" ><?echo $this->html->link(__('Dashboard','pressbackup'), array('menu_type'=>'tools', 'controller'=>'principal', 'function'=>'dashboard'));?></div>
	<div class="tabclear" >&nbsp;</div>
	<div class="tab_subnav"> </div>
	<div  id="tab_content">

		<?echo $this->msg->show('error');?>
		<?echo $this->msg->show('general', array('class'=>"msgbox success"));?>

		<? $dh->print_current_settings(); ?>

		<?if($service){?>

			<hr class="separator"/>

			<h4><?php _e('Backup list','pressbackup'); ?></h4>

			<?$this_site = $all_sites=''; $$from='t_tabactive';?>
			<div class="tab <?echo $this_site;?>" ><?echo $this->html->link(__('This site','pressbackup'), array('controller'=>'principal', 'function'=>'dash_options', 'this_site'));?></div>
			<div class="tabclear" style="" >
				<?if (isset($paginator) && $paginator) { echo  $paginator; } else { echo '&nbsp;'; }?>
			</div>


			<table class="widefat tabbed" cellspacing="0">
				<tr class="alternate">
					<td class="row-title"><?php _e('From','pressbackup'); ?></td>
					<td class="row-title"><?php _e('Type','pressbackup'); ?></td>
					<td class="row-title"><?php _e('Date','pressbackup'); ?></td>
					<td class="row-title"><?php _e('Size','pressbackup'); ?></td>
				</tr>
				<?if(count($bucket_files)==0){?>
					<tr>
						<td><?php _e('Empty','pressbackup'); ?></td>
						<td></td>
						<td></td>
						<td></td>
					</tr>
				<?}?>

				<?$counter=1;?>
				<?foreach($bucket_files as $key => $value){?>
					<tr class="<? if($counter%2==0){echo 'alternate';}?>">
						<?
							//Prepare info to show in table
							$file_name = explode('_', $value['name']);
							$name= str_replace('-', ' ', rawurldecode($file_name[0]));
							$time=date_i18n('M d, Y - ga', $value['time']);
							$size=round(($value['size'] / 1048576), 2);
							$types=array(7=>__('Database','pressbackup'), 5=>__('Themes','pressbackup'), 3=>__('Plugins','pressbackup'), 1=>__('Uploads','pressbackup'));
							$type = explode('-', $file_name[1]);
							$backup_type=array();
							for($i = 0; $i<count($type); $i++){$backup_type[]=$types[$type[$i]];}
						?>
						<td>
							<?echo $name?><br/>
							<div class="options">
								<? //PressBackup has a diferent function for download ?>								
								<? $function = 'backup_get'; if ( $service['id'] == 'Pro' ){ $function = 'backup_get2';}?>
								<?echo $this->html->link(__('Restore','pressbackup'), array('controller'=>'principal', 'function'=>'backup_restore', rawurldecode($value['name'])), array('class'=>'pb_action2 pb_restore'));?>
							</div>
						</td>
						<td><?echo (join(', ',$backup_type))?></td>
						<td><?echo $time?></td>
						<td><?echo $size?>MB</td>
					</tr>

					<?$counter++;?>
				<?}?>
			</table>
		<?}?>

		<div class="loading_bar dnone static" id='pressexpress_status_box'>
			<hr class="separator"/>
			<div><?php _e('Please do not close your window, this may take a few minutes','pressbackup'); ?></div>
			<div><?php _e('Task','pressbackup'); ?>: <span id="pressexpress_status_text">...</span></div>

			<?echo $this->html->img('indicator.gif', array('class'=>'static_image'));?>
			<div id="progressbar" class="dinamic_image"></div>
		</div>

	</div><br/>

	<div id="backup_now_options" style='display:none'>
		<div id="backup_service_options" >
			<div class="msgbox warning wpf-msg" style="max-width: 800px; display: none; ">
				<p><?php _e('Specify what you would like to backup','pressbackup'); ?></p>
			</div>
			<div class="clear"></div>
			<h4><?php _e('What do you want to backup?','pressbackup'); ?></h4>
			<ul class="left">
				<li><input type="checkbox" name="data[preferences][type][]" value ="7" <?if(in_array('7',$backup_types)){echo "checked";}?>> <?php _e('Database','pressbackup'); ?> </li>
				<li><input type="checkbox" name="data[preferences][type][]" value ="5" <?if(in_array('5',$backup_types)){echo "checked";}?>> <?php _e('Themes','pressbackup'); ?></li>
			</ul>
			<ul class="right" style="margin-right:15px">
				<li><input type="checkbox" name="data[preferences][type][]" value ="3" <?if(in_array('3',$backup_types)){echo "checked";}?>> <?php _e('Plugins','pressbackup'); ?></li>
				<li><input type="checkbox" name="data[preferences][type][]" value ="1" <?if(in_array('1',$backup_types)){echo "checked";}?>> <?php _e('Uploads','pressbackup'); ?></li>
			</ul>
			<div class="clear"></div>
		</div>

		<hr class="separator"/>

		<? echo $this->html->link(__('Backup','pressbackup'), array('controller'=>'principal', 'function'=>'backup_start', 'dashboard',), array('class'=>'button ', 'id'=>'press_send_backup_ok'));?>
		<? echo __('or', 'pressbackup') . ' ' . $this->html->link(__('cancel','pressbackup'), "#", array('class'=>'pb_action press_backup_cancel ' ));?>
	</div>

	<div id="backup_download_options" style='display:none'>
		<div id="backup_down_options" >
			<div class="msgbox warning wpf-msg" style="max-width: 800px; display: none; ">
				<p><?php _e('Specify what you would like to backup','pressbackup'); ?></p>
			</div>
			<div class="clear"></div>
			<h4><?php _e('What do you want to backup?','pressbackup'); ?></h4>
			<ul class="left">
				<li><input type="checkbox" name="data[preferences][type][]" value ="7" <?if(in_array('7',$backup_types)){echo "checked";}?>> <?php _e('Database','pressbackup'); ?> </li>
				<li><input type="checkbox" name="data[preferences][type][]" value ="5" <?if(in_array('5',$backup_types)){echo "checked";}?>> <?php _e('Themes','pressbackup'); ?></li>
			</ul>
			<ul class="right" style="margin-right:15px">
				<li><input type="checkbox" name="data[preferences][type][]" value ="3" <?if(in_array('3',$backup_types)){echo "checked";}?>> <?php _e('Plugins','pressbackup'); ?></li>
				<li><input type="checkbox" name="data[preferences][type][]" value ="1" <?if(in_array('1',$backup_types)){echo "checked";}?>> <?php _e('Uploads','pressbackup'); ?></li>
			</ul>
			<div class="clear"></div>
		</div>

		<hr class="separator"/>

		<? echo $this->html->link(__('Backup','pressbackup'), array('controller'=>'principal', 'function'=>'backup_start', 'backup_download'), array('class'=>'button', 'id'=>'press_download_backup_ok'));?>
		<? echo __('or', 'pressbackup') . ' ' . $this->html->link(__('cancel','pressbackup'), "#", array('class'=>'pb_action press_backup_cancel'));?>
	</div>

	<div class="tab_content">
		<h4><?php _e('Signup for PressBackup News to get updates','pressbackup'); ?></h4>
		<form action="http://infinimedia.createsend.com/t/y/s/otyxj/" method="post" id="subForm">
			<div>
				<input type="text" name="cm-otyxj-otyxj" id="otyxj-otyxj" class="longinput" value="<?php _e('Your email address here','pressbackup'); ?>" onfocus="if(jQuery(this).val() == '<?php _e('Your email address here','pressbackup'); ?>'){ jQuery(this).val('') }" onblur="if(jQuery(this).val() == ''){ jQuery(this).val('<?php _e('Your email address here','pressbackup'); ?>') }" />&nbsp;&nbsp;
				<input class="button" type="submit" value="<?php _e('Subscribe','pressbackup'); ?>" />
			</div>
		</form>
		<small><?php printf(__('Found a bug? go to %1$s and send us','pressbackup'), $this->html->link('http://pressbackup.com/contact', 'http://pressbackup.com/contact')) ?> <?echo $this->html->link(__('this info','pressbackup'), array('controller'=>'principal', 'function'=>'host_info'));?></small>
	</div>

	<? echo $this->html->js('jquery-ui-1.8.4.custom.min.js');?>
	<? echo $this->html->css('ui-lightness/jquery-ui.css');?>

	<script type='text/javascript'>
		var delete_confirm="<?php _e('Do you really want to delete this backup?','pressbackup'); ?>",
		delete_status="<?php _e('Deleting backup','pressbackup'); ?>",
		restore_confirm="<?php _e('Do you really want to apply this backup?','pressbackup'); ?>",
		restore_status="<?php _e('Restoring from backup','pressbackup'); ?>",
		backup_types = <?echo json_encode($backup_types);?>;

	<? if($reload) {?>
		var task = '<? echo $reload; ?>',
		reload_url = '<? echo str_replace('&amp;', '&', $this->path->router(array('controller'=>'principal', 'function'=>'dashboard'))); ?>',
		reload_url_fail = '<? echo str_replace('&amp;', '&', $this->path->router(array('controller'=>'principal', 'function'=>'dash_options', false, false, true))); ?>';
		jQuery('#progressbar').progressbar({'value': '0' });
	<?}?>
	</script>


	<? echo $this->html->js("dashboard.js");?>
	<? if($reload) { echo $this->html->js("dashreload.js"); }?>

