<?php

namespace Packetery\DAL;
use InvalidArgumentException;
use PHPSQLParser\PHPSQLCreator;
use PHPSQLParser\PHPSQLParser;

class TablePrefixer {
	/** @var string */
	private $prefix;

	public function __construct($prefix) {
		$this->prefix = $prefix;
	}

	/**
	 * @param string $sql
	 * @return string
	 */
	public function prefix($sql) {
		$PHPSQLParser = new PHPSQLParser($sql);
		$parsed = $PHPSQLParser->parsed;

		if (isset($parsed['SELECT']) || isset($parsed['DELETE'])) {
			$this->prefixForSelectOrDelete($parsed);
		} elseif (isset($parsed['INSERT'])) {
			$this->prefixForInsert($parsed);
		} elseif (isset($parsed['UPDATE'])) {
			$this->prefixForUpdate($parsed);
		} else {
			throw new InvalidArgumentException("Unsupported SQL clause. Only 'FROM', 'INSERT', 'UPDATE', and 'DELETE' SQL clauses are supported for prefixing.");
		}

		$creator = new PHPSQLCreator($parsed);
		return $creator->created;
	}

	private function prefixForSelectOrDelete(&$parsed) {
		foreach ($parsed['FROM'] as $i => $fromClause) {
			$parsed['FROM'][$i]['table'] = $this->prefix . $fromClause['table'];
		}
	}

	/**
	 * @param $parsed
	 * @return void
	 */
	private function prefixForInsert(&$parsed) {
		$parsed['INSERT'][1]['table'] = $this->prefix . $parsed['INSERT'][1]['table'];
	}

	/**
	 * @param $parsed
	 * @return void
	 */
	private function prefixForUpdate(&$parsed) {
		$parsed['UPDATE'][0]['table'] = $this->prefix . $parsed['UPDATE'][0]['table'];
	}
}
