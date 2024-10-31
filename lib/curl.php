<?php

	/*
		curl.php  v1.0
		developer: Perecedero (Ivan Lansky) perecedero@gmail.com
		License: GPL
	*/

	class PressExpressCurl {

		var  $response_code= null;
		var  $response_error= null;
		var  $tmp_file= null;
		var  $buffer= '';
		var  $response_latency= null;
		var  $response_size= null;


		function call($args) {

			date_default_timezone_set('UTC');
			$this->buffer = '';

			if(!isset($args['header']))
			{
				$args['header'] = array();
			}

			if(!isset($args['return_header']))
			{
				$args['return_header'] = false;
			}

			if(!isset($args['no_body']))
			{
				$args['no_body'] = false;
				$args['method_get'] = true;
			}
			else{
				$args['method_get'] = false;
			}

			$curl_handle = curl_init();
			curl_setopt($curl_handle, CURLOPT_URL, $args['url']);
			curl_setopt($curl_handle, CURLOPT_HEADER, $args['return_header'] );
			curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $args['header']);
			curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 5);
			curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Pressbackup/php');
			curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, 0);
			if (isset($args['timeout']))
			{
				curl_setopt($curl_handle, CURLOPT_TIMEOUT, $args['timeout']);
			}
			if (isset($args['cookie']))
			{
				if(is_array($args['cookie'])){
					$cookie = array();
					foreach($args['cookie'] as $key => $value) { $cookie[]=$key.'='.$value;}
					$cookie = join (';', $cookie);
				}
				else{
					$cookie = $args['cookie'];
				}
				curl_setopt($curl_handle, CURLOPT_COOKIE, $cookie);
			}
			curl_setopt($curl_handle, CURLOPT_WRITEFUNCTION, array($this, '__responseWriteCallback'));
			curl_setopt($curl_handle, CURLOPT_HEADERFUNCTION, array($this, '__responseHeaderCallback'));

			if (isset($args['post']))
			{
				curl_setopt($curl_handle, CURLOPT_POST, 1);
				curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $args['post']);
			}

			if (isset($args['saveon']))
			{
				$this->tmp_file =  fopen($args['saveon'], 'wb');
			}

			if (!curl_exec($curl_handle)){

				$this->response_code= curl_errno($curl_handle);
				$this->response_error=curl_error($curl_handle);
				curl_close($curl_handle);

				if ($this->tmp_file != null){
					fclose($this->tmp_file); @unlink($args['saveon']);
				}
				return false;

			}else{

				$this->response_code =curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
				$this->response_latency =curl_getinfo($curl_handle, CURLINFO_TOTAL_TIME);
				$this->response_size = strlen($this->buffer);
				curl_close($curl_handle);

				//close resources
				if ($this->tmp_file != null ){
					@fclose($this->tmp_file);
					$this->tmp_file = null;
				}

				//check for error from server
				if (in_array($this->response_code, array(401, 404, 500, 505)))
				{
					if ($this->response_code == 401){ $this->response_error = "Wrong Credentials";}
					if ($this->response_code == 404){ $this->response_error = "Object Not found";}
					if ($this->response_code == 500){ $this->response_error = "Server Error: 500";}
					if ($this->response_code == 505){ $this->response_error = "Server Error: 505";}

					if(isset($args['saveon'])) {
						@unlink($args['saveon']);
					}

					return false;
				}

				return $this->parse($this->buffer);
			}
		}

		private function __responseWriteCallback(&$curl, &$data) {
			if ( $this->tmp_file !== null)
				return fwrite($this->tmp_file, $data);
			else
				$this->buffer .= $data;
				return strlen($data);
		}

		private function __responseHeaderCallback(&$curl, &$data) {
			return strlen($data);
		}

		private function parse ($buffer){
			$buffer = str_replace('opensearch:', 'os_', $buffer);
			$buffer = str_replace('dc:', 'dc_', $buffer);
			$response = true;
			if( $xml = @simplexml_load_string($buffer))
			{
				$response = array();
				$this->parse_node($response, $xml, $first_time =true);
			}
			return $response;
		}

		private function parse_node (&$array, $node, $first_time=false){
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

		function parse_header ($string){
			$r = explode("\r\n", $string);
			$list = array(); $count = count($r);
			for($i=0; $i < $count; $i++){
				$a = array_shift($r);
				if($a){
					$aux = explode(':', $a);
					if (count($aux) > 0){
						$aux_i=$aux[0]; unset($aux[0]);
						$list[$aux_i] = implode(':', $aux);
					}
				}
			}
			return $list;
		}
	}


?>
