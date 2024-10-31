<?
	require_once 'curl.php';
	if ( (isset ($argv) && isset ($argc)) && ($argc == 3) && ($argv[1] == 'ALLYOUNEEDISLOVE') )
	{
		$info= explode('|ALLYOUNEEDISLOVE|', base64_decode($argv[2]));

		$curl = new PressExpressCurl();
		//create backup file
		$args = array(
			'url' => $info[0],
			'cookie'=>$info[1],
			'timeout'=>3,
		);
		$curl->call($args);
	}

?>
