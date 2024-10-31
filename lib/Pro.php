<?php


class PressExpress {

	private  $API_URL = 'http://pressbackup.com/pro/api';
	private  $API_VERSION = '1';

	private  $authKey = null;
	public  $response_code= null;
	public  $response_error= null;
	public  $buffer= '';
	private  $tmp_file= null;
	private  $fp= null;

	public function __construct($username=null, $authKey = null) {
		if ($authKey !== null )
			$this->setAuth($username, $authKey);
	}

	public  function setAuth($username='nothing', $authKey)
	{
		$this->authKey=base64_encode($username.','.$authKey);
	}

	public function auth()
	{
		$args = array(
			'action' => 'AUTH',
			'post'=>array('version' => $this->API_VERSION),
		);
		return $this->call($args);
	}
	
	public function authExpress()
	{
		$args = array(
			'action' => 'AUTHEXPRESS',
			'post'=>array('version' => $this->API_VERSION),
		);
		return $this->call($args);
	}

	public function check()
	{
		$args = array(
			'action' => 'CHECKSITE',
			'post'=>array('version' => $this->API_VERSION),
		);
		$this->call($args);
		if(!in_array($this->response_code, array(200, 204)))
		{
			return false;
		}
		return true;
	}

	public function putFile($file)
	{
		return $this->putFile_fp($file);
	}

	public function putFile_fp($file)
	{
		global $pressexpress;

		$args = array(
			'action' => 'PUTBACKUPFP',
			'header' => array('Content-Type: application/zip', 'File-Name: '.base64_encode(basename($file))),
		);

		//try to send the chunk 5 times
		$sent = false; $count=0;
		while (!$sent && $count < 3) {
			$this->fp = array( 'file' => @fopen($file, 'rb'), 'size' => filesize($file));
			$sent = $this->call($args);
			$count ++;
			sleep(1);
		}

		if(!$sent || !in_array($this->response_code, array(200, 204)))
		{
			return false;
		}
		return true;
	}

	public function getFile($file, $saveon)
	{
		$args = array(
			'action' => 'GETBACKUP',
			'post'=>array('version' => $this->API_VERSION, 'file'=>$file),
			'saveon'=>$saveon,
		);
		return $this->call($args);
	}

	public function getFile2($file)
	{
		$args = array(
			'action' => 'GETBACKUP2',
			'post'=>array('version' => $this->API_VERSION, 'file'=>$file),
		);
		return $this->call($args);
	}

	public function import($file)
	{
		return false; 
	}

	public  function deleteFile($file)
	{
		$args = array(
			'action' => 'DELETEBACKUP',
			'post'=>array('version' => $this->API_VERSION, 'file'=>$file),
		);
		return $this->call($args);
	}

	public function getFilesList()
	{
		$args = array(
			'action' => 'GETBACKUPSLIST',
			'post'=>array('version' => $this->API_VERSION),
		);
		return $this->call($args);
	}

//
//	GET RESPONSE FUNCTIONS
//

	private function call($args)
	{
		//init settings
		date_default_timezone_set('UTC');
		$this->buffer = '';
		$this->response_code= null;
		$this->response_error= null;
		$this->tmp_file= null;

		if(!isset($args['header']))
		{
			$args['header'] = array();
		}

		$remove_this = '/wp-admin';
		if(strpos ($_SERVER['REQUEST_URI'], $remove_this)=== false)
		{
			$remove_this = '/wp-cron';
		}

		$args['header'][]='From: '.$_SERVER['SERVER_NAME'].substr($_SERVER['REQUEST_URI'], 0 , strpos ($_SERVER['REQUEST_URI'], $remove_this));
		$args['header'][]='Auth: '.$this->authKey;
		$args['header'][]='Date: '.date(DATE_ATOM);

		$curl_handle = null;
		$curl_handle = curl_init(false);
		curl_setopt($curl_handle, CURLOPT_FRESH_CONNECT, true );

		curl_setopt($curl_handle, CURLOPT_URL,  $this->API_URL.'/action/'.$args['action']);
		curl_setopt($curl_handle, CURLOPT_HEADER, 0 );
		curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Pro/php '.get_bloginfo( 'wpurl' ));
		curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $args['header']);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);


		curl_setopt($curl_handle, CURLOPT_NOSIGNAL, true);
		curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 60);
		//curl_setopt($curl_handle, CURLOPT_TIMEOUT, 100);

		curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl_handle, CURLOPT_MAXREDIRS, 2);

		curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, 0);

		curl_setopt($curl_handle, CURLOPT_READFUNCTION, array($this, '__responseReadCallback'));
		curl_setopt($curl_handle, CURLOPT_WRITEFUNCTION, array($this, '__responseWriteCallback'));
		curl_setopt($curl_handle, CURLOPT_HEADERFUNCTION, array($this, '__responseHeaderCallback'));

		if (isset($args['post']))
		{
			curl_setopt($curl_handle, CURLOPT_POST, 1);
			curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $args['post']);
		}

		if (is_array($this->fp))
		{
			curl_setopt($curl_handle, CURLOPT_PUT, true);
			curl_setopt($curl_handle, CURLOPT_INFILE, $this->fp['file']);
			curl_setopt($curl_handle, CURLOPT_BUFFERSIZE, 128);
			if ($this->fp['size'] >= 0) {
				curl_setopt($curl_handle, CURLOPT_INFILESIZE, $this->fp['size']);
				global $pressexpress;
				@$pressexpress->Session->write( 'sent.percent', '0|'.$this->fp['size']);
			}
		}

		else if (isset($args['saveon']))
		{
			$this->tmp_file =  fopen($args['saveon'], 'wb');
		}

		if (!curl_exec($curl_handle))
		{
			$this->response_code= curl_errno($curl_handle);
			$this->response_error=curl_error($curl_handle);
			curl_close($curl_handle);

			if ($this->tmp_file != null){
				@fclose($this->tmp_file); @unlink($args['saveon']);
			}
			if (is_array($this->fp)){
				@fclose($this->fp['file']);
				$this->fp = null;
			}
			return false;
		}
		else
		{
			$this->response_code =curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
			curl_close($curl_handle);

			//close resources
			if ($this->tmp_file != null ){
				@fclose($this->tmp_file);
				$this->tmp_file = null;
			}
			if (is_array($this->fp)){
				@fclose($this->fp['file']);
				$this->fp = null;
			}

			//check for error from server
			if (in_array($this->response_code, array(401, 404, 500, 505)))
			{
				if ($this->response_code == 401){ $this->response_error = "Wrong Credentials";}
				if ($this->response_code == 404){ $this->response_error = "Object Not found";}
				if ($this->response_code == 500){ $this->response_error = "Server Error";}
				if ($this->response_code == 505){ $this->response_error = "Server Error";}

				if(isset($args['saveon'])) {
					@unlink($args['saveon']);
				}

				return false;
			}

			//parse when ok
			return $this->parse($this->buffer);
		}
	}

	private function __responseReadCallback(&$curl, $fp, $len) {
		if (!is_array($this->fp) || feof($this->fp['file'])) {return '';}
		else{
			global $pressexpress;
			@$pressexpress->Session->write( 'sent.percent', (@ftell($this->fp['file'])).'|'.$this->fp['size']);
			//file_put_contents($pressexpress->Path->Dir['LOGTMP'] . DS .'fp.log', $len."\n" , FILE_APPEND);
			return fread($this->fp['file'], $len);
		}
	}

	private function __responseWriteCallback(&$curl, &$data) {
		if ( $this->tmp_file != null)
			return fwrite($this->tmp_file, $data);
		else
			$this->buffer .= $data;
			return strlen($data);
	}

	private function __responseHeaderCallback(&$curl, &$data) {
		return strlen($data);
	}

	private function parse ($buffer)
	{
		$response = true;
		if($buffer) { $response = $buffer;}
		if( $xml = @simplexml_load_string($buffer)) {
			$response = array();
			$this->parse_node($response, $xml, $first_time =true);
		}
		return $response;
	}

	private function parse_node (&$array, $node, $first_time=false)
	{
		if(!$first_time)
		{
			$i=0;
		}
		foreach($node->children() as $name => $xmlchild)
		{
			if(count($xmlchild) == 0)
			{
				$array[$name]= (string)$node->$name;
			}
			else
			{
				if (isset($i))
				{
					$this->parse_node ($array[$i][$name], $xmlchild);
					$i++;
				}
				else
				{
					$this->parse_node ($array[$name], $xmlchild);
				}
			}
		}
	}
	
	public function wasInstall()
	{
		$args = array(
			'action' => 'WASINSTALL',
			'post'=>array('version' => $this->API_VERSION),
		);
		return $this->call($args);
	}
	
	public function wasConfigured()
	{
		$args = array(
			'action' => 'WASCONFIGURED',
			'post'=>array('version' => $this->API_VERSION),
		);
		return $this->call($args);
	}
}
