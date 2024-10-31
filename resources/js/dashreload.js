/*
* Run ajax and check background process status
* this function will call itself untill background finish
*/
function pressexpress_chk_status(task) {

	/*
		data: { "action" : "x", "task_now" : "z", "status": "r", response: "s"}
		action > finish | wait
		status (backprocess) >  ok | fail | percent
		task_now > *
		response > *
	*/
	jQuery.post(ajaxurl, {action:"pressexpress_check_backupnow_status", 'task': task, 'cookie': encodeURIComponent(document.cookie)}, function(data) {
		info=jQuery.parseJSON(data);

		if (info.action == 'finish') {

			if(reload_url){
				setTimeout('pressexpress_reload_page( info.status,  info.response)', 250);
				return true;
			}

			status_box_hide();
			return true;
		}

		if (info.action == 'wait') {

			switch (info.status) {

				case 'percent':
					status_box_show ('dinamic', info.task_now, ( parseInt( info.response.current ) * 100 ) / parseInt( info.response.total ) );
					setTimeout('pressexpress_chk_status("'+task+'")', 1000);
				break;
				case 'ok':
					status_box_show ('static', info.task_now );
					setTimeout('pressexpress_chk_status("'+task+'")', 1000);
				break;
				case 'fail':

					process_fail++;
					if( process_fail == 10 ){

						if(reload_url){
							setTimeout('pressexpress_reload_page( "fail")', 250);
							return false;
						}

						status_box_hide();
						return false;
					}

					status_box_show ('static', ' ... ' );
					setTimeout('pressexpress_chk_status("'+task+'")', 2000);

				break;
			}

		}
	});
}

/*
* show the status box with a progressbar or a loading image
* @type string: show a progress bar or a loading image (dinamic | static)
* @msg string: text to show on status box
* @value integer: for dinamic type, the value of the progress bar [0..100]
*/
function status_box_show (type, msg, value) {

	if(type == 'dinamic' ) { 
		jQuery("#pressexpress_status_box").removeClass('static');
		jQuery("#pressexpress_status_box").addClass('dinamic');
		jQuery("#progressbar").progressbar( "value" , value );
	} else {
		jQuery("#pressexpress_status_box").removeClass('dinamic');
		jQuery("#pressexpress_status_box").addClass('static');
	}
	jQuery("#pressexpress_status_text").html(msg);
	jQuery("#pressexpress_status_box").show('fast');

}

/*
* hide the status box
*/
function status_box_hide () {
	jQuery("#pressexpress_status_box").hide('fast');
}


/*
* Perform a redirect when a background process finish
* @status string: status of background process
* @data mixed: returned data by background process
*/
function pressexpress_reload_page(status, data) {

	status_box_hide ();

	var args = "";
	if (typeof(data) != 'undefined' && data != '' ) { args = '&fargs[]='+data.file; }

	if(status == "fail" && typeof(reload_url_fail) != 'undefined' ){
		document.location.href=reload_url_fail + args;
	}
	else if ( status == "ok" && typeof(reload_url) != 'undefined' ) {
		document.location.href=reload_url + args;
	}
}

/*
* Number of times checked for background process start
* after 10 times of no notice about process start, it is considered dead
*/
var process_fail = 0;

/*
* Begin check background process status
*/
if (task =="backup_download") {
	setTimeout('pressexpress_chk_status("download")', 200);
}else{
	setTimeout('pressexpress_chk_status("save")', 200);
}




