<?php

namespace MongoMinify;

class Collection {
	
	public $name = '';
	public $namespace = '';
	public $db;
	public $native;


	public function __construct($name, $db)
	{
		$this->name = $name;
		$this->db = $db;
		$this->client = $db->client;
		$this->namespace = $db->name . '.' . $this->name;
		$this->native = $db->native->selectCollection($this->name);

		// Apply schema to collection
		$this->setSchemaByName($this->namespace);
	}


	/**
	 * Compression
	 * This is useful for testing what a document will look like during development or debugging
	 */
	public function compress($data)
	{
		$document = new Document($data, $this);
		$document->compress();
		return $document->data;
	}


	/**
	 * Save Document
	 * @param  [type] $data    [description]
	 * @param  array  $options [description]
	 * @return [type]          [description]
	 */
	public function save(&$data, Array $options = array())
	{
		$document = new Document($data, $this);
		$document->compress();
		$save = $this->native->save($document->data, $options);
		$data = $document->data;
		return $save;
	}


	/**
	 * Insert new document
	 * @param  [type] $document [description]
	 * @param  array  $options  [description]
	 * @return [type]           [description]
	 */
	public function insert(&$data, Array $options = array())
	{
		$document = new Document($data, $this);
		$document->compress();
		$insert = $this->native->insert($document->data, $options);
		$data = $document->data;
		return $insert;
	}


	/**
	 * Find document
	 * @param  array  $document [description]
	 * @return [type]           [description]
	 */
	public function findOne(Array $query = array(), Array $options = array())
	{
		$cursor = $this->find($query, $options);
		return $cursor->getNext();
	}
	public function find(Array $query = array(), Array $options = array())
	{
		$document = new Document($query, $this);
		$document->compress();
		$cursor = $this->native->find($document->data, $options);
		return $cursor;
	}


	/**
	 * Apply internal schema
	 * @param [type] $schema [description]
	 */
	public function setSchemaByName($schema_name = null)
	{
		if ( ! $schema_name)
		{
			$schema_name = $this->namespace;
		}
		if (strpos($schema_name, '.') === false)
		{
			$schema_name = $this->db->name . '.' . $schema_name;
		}
		$schema_file = $this->client->schema_dir . '/' . $schema_name . '.php';
		if (file_exists($schema_file))
		{
			$schema = include $schema_file;
			$this->setSchema($schema);
		}
	}
	public function setSchema(Array $schema = array())
	{
		$this->schema = array();
		$this->schema_raw = $schema;
		$this->setSchemaArray($schema);
	}
	private function setSchemaArray(Array $array, $namespace = null)
	{
		foreach ($array as $key => $value)
		{
			$subkey = ($namespace ? $namespace . '.' : '') . $key;
			if (isset($value['subset']))
			{
				$this->setSchemaArray($value['subset'], $subkey);
				unset($value['subset']);
			}
			$this->schema[$subkey] = $value;
		}
	}


	/**
	 * Batch Insert
	 */
	public function batchInsert(Array &$documents, Array $options = array())
	{
		$documents_compressed = array();
		foreach ($documents as $data)
		{
			$document = new Document($data, $this);
			$document->compress();
			$documents_compressed[] = $document->data;
		}
		$this->native->batchInsert($documents_compressed);
	}


	/**
	 * Drop
	 */
	public function drop()
	{
		$this->native->drop();
	}


	/**
	 * Ensure Index
	 */
	public function ensureIndex(Array $keys, Array $options = array())
	{
		$this->native->ensureIndex($keys, $options);
	}

}