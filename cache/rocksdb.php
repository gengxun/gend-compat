<?php

class Gend_RocksDb extends Gend
{
	private static $objArray = array();

	private $obj = null;
	private $config = array(
		'options' => array(
			'create_if_missing' => true,
			'error_if_exists' => false,
			'paranoid_checks' => false,
			'merge_operator' => "\n",
			'max_open_files' => 65535,
		),

		'readoptions' => array(
			'verify_check_sum' => false,
			'fill_cache' => true,
		),

		'writeoptions' => array(
			'sync' => false
		),
	);

	public function __construct($dbpath, $merge_sep = "\n")
	{
		$this->config["options"]["merge_operator"] = $merge_sep;
		$this->obj = new RocksDB($dbpath, $this->config['options'], $this->config['readoptions'], $this->config['writeoptions']);
	}

	public function get($key)
	{
		if (is_string($key) && strlen($key)) {
			return $this->obj->get($key);
		}
		return false;
	}

	public function put($key, $val)
	{
		if (is_string($key) && strlen($key)) {
			return $this->obj->put($key, $val);
		}
		return false;
	}

	public function merge($key, $val)
	{
		if (is_string($key) && strlen($key)) {
			return $this->obj->merge($key, $val);
		}
		return false;
	}

	public function del($key)
	{
		//delete
		if (is_string($key) && strlen($key)) {
			return $this->obj->delete($key);
		}
		return false;
	}
}