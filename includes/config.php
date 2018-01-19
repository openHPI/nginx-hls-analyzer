<?php
// $Id: config.php 11 2009-10-09 13:53:15Z bvamos $

//$VERSION = '1.0';
//$VERSION = '1.1'; // 2009-09-16
$VERSION = '1.2'; // 2009-10-09


/**
 * Configuration class
 */
class Config{
	var $config_file;
	var $error = '';
	var $items = array();

	function Config($filepath = 'fmsloganalyzer.ini'){
		if(!$filepath){
			$this->error = 'ERROR: Empty config file path';
			return FALSE;
		}
		if(!file_exists($filepath)){
			$this->error = 'ERROR: Config file not found: '.$filepath;
			return FALSE;
		}
		$this->config_file = $filepath;
		return $this->load();
	}

	function load(){
		$CONFIG = @parse_ini_file($this->config_file, TRUE);
		if(!$CONFIG){
			$this->error = 'ERROR: Unable to open config file: '.$this->config_file;
			return FALSE;
		}
		$this->items = $CONFIG;
		return TRUE;
	}

	function error_msg(){
		return $this->error;
	}
}
?>