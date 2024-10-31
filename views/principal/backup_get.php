<?
	global $pressbackup;
	smartReadFile($file, "backup_".date('Y_m_d').".zip","application/zip");

	/*	
	//clean log && tmp dir
	$pressbackup->import('misc.php');
	$misc = new PressExpressMisc();
	$misc->perpare_folder($pressbackup->Path->Dir['LOGTMP']);
	$misc->perpare_folder($pressbackup->Path->Dir['PBKTMP']);
	*/
?>
