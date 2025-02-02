<?php

/*
	WordPress Framework, HTML class v1.0
	developer: Perecedero (Ivan Lansky) perecedero@gmail.com
*/

/*
configuracion para saber cuanto tiempo dura la seccion
quizas un funcion para start session que haga los checkeos
de tiempo etc, o que haga un destroy y un start

*/


class w2pf_session_pressExpress {

	var $path = null;
	var $config = null;
	var $id = null;
	var $session_name = null;
	var $writing = null;

	function __construct($path, $config){

		$this->path = &$path;
		$this->config = &$config;

		//get a unique id for user logued
		foreach ($_COOKIE as $key => $value) { if(preg_match("/^wordpress_logged_in_(.)*$/", $key)) {$this->id=md5($value); break;} }
		if (!$this->id){ $this->id = 'Global'; }

		//session_name
		$this->session_name = 'framepress_session_' . $this->config->read('prefix');

		//create a global session if not exist
		if ( !$session = get_option($this->session_name) ) {
			$session = array (
				$this->id => array (
					'time' => strtotime('now'),
					'data' => array(),
				),
			);
		}

		//remove old sessions
		foreach ($session as $key => $value ) {
			if ( ( $value['time'] + $this->config->read('session.time') ) < strtotime('now') ) {
				unset( $session[ $key ] );
			}
		}

		//add user session
		if(!isset($session[$this->id])){
			$session[$this->id] = array (
				'time' => strtotime('now'),
				'data' => array(),
			);
		}

		update_option ($this->session_name,$session);
	}

	function read ($key, $global = null)
	{
		$id = $this->id; if ($global) {$id = 'Global';}

		$session =get_option($this->session_name );
		$session[$id]['time'] = strtotime('now');
		update_option ($this->session_name, $session);

		return ( isset($session[$id]['data'][$key]) )? $session[$id]['data'][$key] : null ;
	}

	function check ($key, $global = null)
	{
		$id = $this->id; if ($global) {$id = 'Global';}

		$session = get_option($this->session_name);
		$session[$id]['time'] = strtotime('now');
		update_option ($this->session_name,$session);

		return  isset($session[$id]['data'][$key]);
	}

	function delete ($key, $global = null)
	{
		$id = $this->id; if ($global) {$id = 'Global';}

		$session = get_option($this->session_name);
		$session[$id]['time'] = strtotime('now');
		unset($session[$id]['data'][$key]);
		update_option ($this->session_name, $session );
		return true;
	}

	function destroy ()
	{
		$session = get_option($this->session_name);
		$session[$this->id]['time'] = strtotime('now');
		$session[$this->id]['data'] = array();
		update_option ($this->session_name, $session);
		return true;
	}

	function write ($key, $value, $global = null)
	{
		$id = $this->id; if ($global) {$id = 'Global';}

		$session = get_option($this->session_name);
		$session[$id]['time'] = strtotime('now');
		$session[$id]['data'][$key] = $value;
		update_option ($this->session_name, $session );
		return true;
	}

	private function aprint () {
		print_r(get_option($this->session_name) );

	}

}

?>
