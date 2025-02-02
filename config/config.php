<?
	/*
		this will be the prefix for all controllers,
		with this you can get the same name for
		controller on different plugins
	*/

	$fp_config['prefix'] = 'pressExpress';

	/*
		this will be the session duration time in seconds
	*/
	$fp_config['session.time'] = 3600 * 4;

	/*
		activate the use of temporal folder. FramePress will use
		the system tmp folder if avaiable, otherwise it will use
		plugin tmp folder
	*/
	$fp_config['use.tmp'] = true;

	/*
		Activate the use internationalization languages. 
		FramePress will call the needed functions to can traslate
		your plugin from de .mo files under languages folder.
		The domain name used will be the value of "prefix"
		read: http://codex.wordpress.org/I18n_for_WordPress_Developers
	*/
	$fp_config['use.i18n'] = true;

	/*
		Add your on config values here
	*/
	$fp_config['S3.bucketname'] = 'pressbackups';

?>
