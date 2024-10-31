<?php

	//infinite time for this script


	function smartReadFile($location, $filename, $mimeType='application/octet-stream')
	{
		ob_end_clean();

		//check if file exist
		if(!file_exists($location))
		{
			header ("HTTP/1.0 404 Not Found");
			return;
		}

		//check if we can open it
		if(!$fm=@fopen($location,'rb'))
		{
				header ("HTTP/1.0 505 Internal server error");
				exit;
		}
		else
		{
			fclose($fm);
		}

		$size=filesize($location);
		$time=date('r',filemtime($location));

		$chunk = 2048;
		$chunk_per_loop = 20;
		$loop = ceil($size / $chunk * $chunk_per_loop);
		$fcount = $fpos = 0;

		header('HTTP/1.1 200 OK');
		header("Date: ".date('r',strtotime('now')));
		header("Last-Modified: {$time}");
		header('Content-Length:'.$size);
		header("Content-Type: {$mimeType}");
		header('Cache-Control: public, must-revalidate, max-age=0');
		header('Pragma: no-cache');
		header("Content-Disposition: inline; filename={$filename}");
		header("Content-Transfer-Encoding: binary\n");
		header('Connection: close');

		for ($i=0; $i < $loop; $i ++)
		{
			$fm=@fopen($location,'rb');
			fseek($fm, $fpos);

			ob_start();
				while(!feof($fm) && $fcount < ($chunk_per_loop * $chunk) && (connection_status()==0))
				{
					echo fread($fm,$chunk);
					$fcount += $chunk;
					$fpos += $chunk;
				}
			ob_flush();
			ob_end_clean();

			fclose($fm);
			$fcount = 0;
		}
		exit();
	}



?>
