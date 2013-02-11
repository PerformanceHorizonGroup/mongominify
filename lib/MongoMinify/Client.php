<?php

namespace MongoMinify;

class Client {

	public $native;
	public $debug = false;
	public $schema_dir = './';
	

	/**
	 * Initializer
	 * @param Array $options Connection Options
	 */
	public function __construct($server = 'mongodb://localhost:27017', array $options = array())
	{

		// Parse MongoDB Path Info
		if ( ! empty($options['db']))
		{
			$db_name = $options['db'];
		}
		else
		{
			$uri = parse_url($server);
			$db_name = isset($uri['path']) ? substr($uri['path'], 1) : 'test';
		}

		// Native connection
		$this->native = new \MongoClient($server, $options);

		// Select Database for default reference
		if ($db_name)
		{
			$this->selectDb($db_name);
		}
		
	}


	/**
	 * Select Database
	 */
	public function __get($name)
	{
		return $this->selectDb($name);
	}


	/**
	 * Select Collection
	 */
	public function selectDb($name)
	{
		$db = new Db($name, $this);
		return $db;
	}

}