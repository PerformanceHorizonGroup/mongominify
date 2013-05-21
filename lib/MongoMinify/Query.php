<?php

namespace MongoMinify;

class Query
{

    public $state = 'normal';
    public $data = array();
    public $compressed = array();
    protected $collection = array();

    public function __construct(array $data = array(), $collection = null)
    {
        $this->collection = $collection;
        $this->data = $data;
    }

    /**
     * Data Compression
     */
    public function compress()
    {
        if (!$this->collection->schema) {
            $this->compressed =& $this->data;
            return;
        }
        if ($this->state !== 'compressed' && $this->collection) {
            $this->compressed = $this->applyCompression($this->data);
            $this->asDotSyntax();
            $this->state = 'compressed';
        }
    }
    private function applyCompression($document, $parent = null)
    {

        // If is an array, loop through and apply rules (I don't think this is needed, will see if any use cases arise)
        /*
        if (isset($document[0]) && is_array($document[0])) {
            foreach ($document as $key => $value) {
                $document[$key] = $this->applyCompression($value, $parent);
            }
            return $document;
        }
        */

        // Normalize doc delimited keys
        foreach ($document as $key => $value) {
            if (strpos($key, '.') !== false) {
                list ($parent_key, $child_key) = explode('.', $key, 2);
                if (!isset($document[$parent_key])) {
                    $document[$parent_key] = array();
                }
                $document[$parent_key][$child_key] = $value;
                unset($document[$key]);
            }
        }

        // Documents are applied as key/value
        $doc = array();
        foreach ($document as $key => $value) {

            $namespace = ($parent ? $parent . '.' : '') . $key;

            // Fix $in queries
            if ($key === '$in' && isset($this->collection->schema[$parent]['values'])) {
                $values = $this->collection->schema[$parent]['values'];
                $values = array_flip($values);
                foreach ($value as $valkey => $val) {
                    if (isset($values[$val])) {
                        $value[$valkey] = $values[$val];
                    }
                }

            // Loop over arrays recursively
            } elseif (is_array($value)) {
                $value = $this->applyCompression($value, $namespace);

            // Handle actual value conversion
            } elseif (isset($this->collection->schema[$namespace]['values'])) {
                $values = $this->collection->schema[$namespace]['values'];
                $values = array_flip($values);
                if (isset($values[$value])) {
                    $value = $values[$value];
                }
            }

            // Apply values
            $short = isset($this->collection->schema_index[$namespace])
                ? $this->collection->schema_index[$namespace]
                : $key;
            $doc[$short] = $value;
        }
        return $doc;
    }


    /**
     * As dot syntax for index ensuring
     * TODO: This is a quick hack to get indexes working with embedded document syntax
     */
    private $dotSyntax = array();
    public function asDotSyntax()
    {
        $this->applyDotSyntax($this->compressed);
        $this->compressed = $this->dotSyntax;
        return $this->dotSyntax;
    }
    private function applyDotSyntax($data, $ns = '')
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (strpos($key, '$') === 0) {
                    $this->dotSyntax[$ns][$key] = $value;
                } else {
                    $this->applyDotSyntax($value, ($ns ? $ns . '.' : '') . $key);
                }
            }
            return;
        }
        $this->dotSyntax[$ns] = $data;
    }
}
