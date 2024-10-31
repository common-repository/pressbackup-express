/*
 * check status Cron
*/
function pressexpress_check_cron_status()
{
	jQuery.post(ajaxurl, {action:"pressexpress_wizard_cron_status", 'cookie': encodeURIComponent(document.cookie)}, function(data) {
		info=jQuery.parseJSON(data);
		
		if (info.status == "ok")
		{
			jQuery('#pressexpress_mess_wait').hide();
			jQuery('#pressexpress_bt_start').show();

		}else{
			count_fails++;
			if(count_fails == 10){
				document.location.href = reload_url_fail;
			}else{
				setTimeout('pressexpress_check_cron_status()', 1500);
			}
		}
	});
}

/*
* Number of times checked for background process start
* after 4 time of no notice about process start, it is considered dead
*/
var count_fails = 0;

pressexpress_check_cron_status();
