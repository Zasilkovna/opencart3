<?php

namespace Packetery\DAL;

use InvalidArgumentException;
use PHPSQLParser\PHPSQLCreator;
use PHPSQLParser\PHPSQLParser;

class TablePrefixer {
    /** @var string */
    private $prefix;

    /**
     * @param string $prefix
     */
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
            $message = <<<EOT
Unsupported SQL clause. Only 'FROM', 'INSERT', 'UPDATE', and 'DELETE' SQL clauses 
are supported for prefixing.
EOT;

            throw new InvalidArgumentException($message);
        }
        $creator = new PHPSQLCreator($parsed);

        return $creator->created;
    }

    /**
     * @param array $parsed
     * @return void
     */
    private function prefixForSelectOrDelete(array &$parsed) {
        foreach ($parsed['FROM'] as $i => $fromClause) {
            $parsed['FROM'][$i]['table'] = $this->prefix . $fromClause['table'];
        }
    }

    /**
     * @param array $parsed
     * @return void
     */
    private function prefixForInsert(array &$parsed) {
        $parsed['INSERT'][1]['table'] = $this->prefix . $parsed['INSERT'][1]['table'];
    }

    /**
     * @param array $parsed
     * @return void
     */
    private function prefixForUpdate(array &$parsed) {
        $parsed['UPDATE'][0]['table'] = $this->prefix . $parsed['UPDATE'][0]['table'];
    }
}
