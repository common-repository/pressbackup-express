<?
	//HERE YOU CAN ADD YOUR OWN PATHS
	//YOU WILL CAN ACCESS THEM VIA Path->Dir atribute.
	//use DS "directory separator" for paths

	$path['PBKTMP'] = $this->Dir['SYSTMP']. DS . 'pressback.' . substr(md5(get_bloginfo('wpurl')), 0,5);
	$path['LOGTMP'] = $this->Dir['SYSTMP']. DS . 'presslog.' . substr(md5(get_bloginfo('wpurl')), 0,5);
?>
